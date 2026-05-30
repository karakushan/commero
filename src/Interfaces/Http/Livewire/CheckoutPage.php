<?php

namespace Commero\Interfaces\Http\Livewire;

use Commero\Models\PaymentMethod;
use Commero\Models\ShippingMethod;
use Commero\Models\User;
use Commero\Services\CartService;
use Commero\Services\NovaPoshtaService;
use Commero\Services\OrderPlacementService;
use Commero\Support\Locales;
use Commero\Support\Phone;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class CheckoutPage extends Component
{
    public string $locale;

    public string $firstName = '';
    public string $lastName = '';
    public string $phone = '';
    public string $email = '';
    public bool $registerCustomer = false;

    public bool $hasOtherRecipient = false;
    public string $shippingFirstName = '';
    public string $shippingLastName = '';
    public string $shippingPhone = '';
    public string $shippingEmail = '';
    public string $deliveryCityRef = '';
    public string $deliveryCityName = '';
    public string $deliveryWarehouseRef = '';
    public string $deliveryWarehouseName = '';
    public string $deliveryStreet = '';
    public string $deliveryHouse = '';
    public string $deliveryApartment = '';

    public ?int $selectedShippingMethod = null;
    public ?int $selectedPaymentMethod = null;
    public string $comment = '';

    public function mount(?string $locale = null): void
    {
        $this->locale = Locales::resolve($locale);

        $this->setDefaultCheckoutSelections();
        $this->fillFromUser(auth()->user());
    }

    public function updatedHasOtherRecipient(bool $value): void
    {
        if ($value) {
            return;
        }

        $this->shippingFirstName = '';
        $this->shippingLastName = '';
        $this->shippingPhone = '';
        $this->shippingEmail = '';
    }

    public function updatedSelectedShippingMethod(?int $value): void
    {
        if ($value === null) {
            return;
        }

        $shippingMethod = ShippingMethod::query()->find($value);

        if ($shippingMethod?->isNovaPoshta()) {
            $this->clearDeliveryAddress();

            return;
        }

        if (! $shippingMethod) {
            return;
        }

        if (! $shippingMethod->isNovaPoshta()) {
            $this->clearDeliveryWarehouse();
        }
    }

    public function placeOrder(CartService $cartService, OrderPlacementService $orderPlacementService): void
    {
        $cart = $cartService->getCart();

        if (($cart['count'] ?? 0) < 1 || empty($cart['items'])) {
            $this->addError('cart', __('Your cart is empty. Add at least one product before checkout.'));

            return;
        }

        $shippingMethods = ShippingMethod::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('is_active', true)
            ->get(['id', 'code', 'name']);

        $paymentMethods = PaymentMethod::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('is_active', true)
            ->get(['id', 'code', 'name']);

        $validated = $this->validate($this->rules($shippingMethods, $paymentMethods), [], $this->validationAttributes());

        if (! auth()->check() && ($validated['registerCustomer'] ?? false) && $this->existingCustomerConflicts($validated)) {
            return;
        }

        $shippingMethod = $shippingMethods->firstWhere('id', (int) $validated['selectedShippingMethod']);
        $paymentMethod = $paymentMethods->firstWhere('id', (int) $validated['selectedPaymentMethod']);

        if (! $shippingMethod || ! $paymentMethod) {
            $this->addError('checkout', __('Unable to resolve the selected delivery or payment method.'));

            return;
        }

        /** @var User|null $user */
        $user = auth()->user();

        $order = $orderPlacementService->placeCheckoutOrder(
            validated: $validated,
            cart: $cart,
            user: $user,
            shippingMethod: $shippingMethod,
            paymentMethod: $paymentMethod,
        );

        $cartService->clear();
        $this->resetCheckoutForm();

        $this->redirectRoute(
            $this->thankYouRouteName(),
            array_merge($this->checkoutRouteParameters(), ['orderNumber' => $order->id]),
            navigate: true
        );
    }

    public function render(CartService $cartService, NovaPoshtaService $novaPoshtaService): View
    {
        $cart = $cartService->getCart();

        $shippingMethods = ShippingMethod::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->withTranslationsFor(app()->getLocale())
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();

        $freeDeliveryLimit = 2000;
        $cartCostNumeric = (float) ($cart['total_numeric'] ?? 0);
        $freeDeliveryLeft = $freeDeliveryLimit > 0 ? max(0, $freeDeliveryLimit - $cartCostNumeric) : 0;
        $freeDeliveryProgress = $freeDeliveryLimit > 0 ? min(100, ($cartCostNumeric / $freeDeliveryLimit) * 100) : 0;

        return view('shophats::pages.checkout', [
            'cart' => $cart,
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods,
            'selectedShippingMethod' => $this->selectedShippingMethod,
            'selectedPaymentMethod' => $this->selectedPaymentMethod,
            'freeDeliveryLimit' => $freeDeliveryLimit,
            'freeDeliveryLeft' => $freeDeliveryLeft,
            'freeDeliveryProgress' => $freeDeliveryProgress,
            'freeDeliveryProgressClass' => $this->getProgressClass($freeDeliveryProgress),
            'isLoggedIn' => auth()->check(),
            'novaPoshtaConfigured' => $novaPoshtaService->hasApiKey(),
            'novaPoshtaMethodIds' => $shippingMethods
                ->filter(fn (ShippingMethod $shippingMethod): bool => $shippingMethod->isNovaPoshta())
                ->pluck('id')
                ->map(fn (int $id): int => (int) $id)
                ->values()
                ->all(),
        ])->layout('shophats::layouts.base', [
            'title' => __('Checkout'),
        ]);
    }

    protected function rules(Collection $shippingMethods, Collection $paymentMethods): array
    {
        $requiresNovaPoshtaWarehouse = $this->selectedShippingMethodRequiresNovaPoshta($shippingMethods);

        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'phone' => $this->ukrainianPhoneRules(required: true),
            'email' => ['required', 'email', 'max:255'],
            'registerCustomer' => ['boolean'],
            'hasOtherRecipient' => ['boolean'],
            'shippingFirstName' => ['nullable', 'required_if:hasOtherRecipient,true', 'string', 'max:255'],
            'shippingLastName' => ['nullable', 'required_if:hasOtherRecipient,true', 'string', 'max:255'],
            'shippingPhone' => $this->ukrainianPhoneRules(required: $this->hasOtherRecipient),
            'shippingEmail' => ['nullable', 'email', 'max:255'],
            'deliveryCityRef' => ['required', 'string', 'max:255'],
            'deliveryCityName' => ['required', 'string', 'max:255'],
            'deliveryWarehouseRef' => [$requiresNovaPoshtaWarehouse ? 'required' : 'nullable', 'string', 'max:255'],
            'deliveryWarehouseName' => [$requiresNovaPoshtaWarehouse ? 'required' : 'nullable', 'string', 'max:255'],
            'deliveryStreet' => ['nullable', 'string', 'max:255'],
            'deliveryHouse' => ['nullable', 'string', 'max:255'],
            'deliveryApartment' => ['nullable', 'string', 'max:255'],
            'selectedShippingMethod' => [
                'required',
                'integer',
                Rule::in($shippingMethods->pluck('id')->all()),
                function (string $attribute, mixed $value, Closure $fail) use ($requiresNovaPoshtaWarehouse): void {
                    if ($requiresNovaPoshtaWarehouse && ! app(NovaPoshtaService::class)->hasApiKey()) {
                        $fail(__('Nova Poshta delivery is temporarily unavailable.'));
                    }
                },
            ],
            'selectedPaymentMethod' => ['required', 'integer', Rule::in($paymentMethods->pluck('id')->all())],
            'comment' => ['nullable', 'string'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'firstName' => __('First name *'),
            'lastName' => __('Last name *'),
            'phone' => __('Phone *'),
            'email' => __('E-mail *'),
            'registerCustomer' => __('Register to save all order information and contact details.'),
            'shippingFirstName' => __('Recipient first name'),
            'shippingLastName' => __('Recipient last name'),
            'shippingPhone' => __('Recipient phone'),
            'shippingEmail' => __('Recipient e-mail'),
            'deliveryCityRef' => __('Delivery city'),
            'deliveryCityName' => __('Delivery city'),
            'deliveryWarehouseRef' => __('Delivery branch'),
            'deliveryWarehouseName' => __('Delivery branch'),
            'deliveryStreet' => __('Street'),
            'deliveryHouse' => __('House'),
            'deliveryApartment' => __('Apartment'),
            'selectedShippingMethod' => __('Shipping method'),
            'selectedPaymentMethod' => __('Payment method'),
            'comment' => __('Order comment'),
        ];
    }

    private function fillFromUser(?User $user): void
    {
        if (! $user) {
            return;
        }

        [$fallbackFirstName, $fallbackLastName] = $this->splitFullName((string) ($user->name ?? ''));

        $this->firstName = trim((string) ($user->first_name ?: $fallbackFirstName));
        $this->lastName = trim((string) ($user->last_name ?: $fallbackLastName));
        $this->phone = trim((string) ($user->phone ?? ''));
        $this->email = trim((string) ($user->email ?? ''));
        $this->selectedShippingMethod = $this->resolveUserShippingMethodId($user);
        $this->deliveryCityRef = trim((string) ($user->delivery_city_ref ?? ''));
        $this->deliveryCityName = trim((string) ($user->delivery_city_name ?? ''));
        $this->deliveryWarehouseRef = trim((string) ($user->delivery_warehouse_ref ?? ''));
        $this->deliveryWarehouseName = trim((string) ($user->delivery_warehouse_name ?? ''));
        $this->deliveryStreet = trim((string) ($user->delivery_street ?? ''));
        $this->deliveryHouse = trim((string) ($user->delivery_house ?? ''));
        $this->deliveryApartment = trim((string) ($user->delivery_apartment ?? ''));
    }

    private function resetCheckoutForm(): void
    {
        $this->resetValidation();
        $this->resetErrorBag();

        $this->firstName = '';
        $this->lastName = '';
        $this->phone = '';
        $this->email = '';
        $this->registerCustomer = false;

        $this->hasOtherRecipient = false;
        $this->shippingFirstName = '';
        $this->shippingLastName = '';
        $this->shippingPhone = '';
        $this->shippingEmail = '';
        $this->deliveryCityRef = '';
        $this->deliveryCityName = '';
        $this->deliveryWarehouseRef = '';
        $this->deliveryWarehouseName = '';
        $this->deliveryStreet = '';
        $this->deliveryHouse = '';
        $this->deliveryApartment = '';
        $this->comment = '';

        $this->setDefaultCheckoutSelections();
        $this->fillFromUser(auth()->user());
    }

    private function ukrainianPhoneRules(bool $required): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'max:255'];
        $rules[] = function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! Phone::isValidUkrainian((string) $value)) {
                $fail(__('Enter a valid Ukrainian phone number.'));
            }
        };

        return $rules;
    }

    private function existingCustomerConflicts(array $validated): bool
    {
        $email = mb_strtolower(trim((string) ($validated['email'] ?? '')));
        $normalizedPhone = Phone::normalize((string) ($validated['phone'] ?? ''));

        $emailExists = $email !== '' && User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();

        $phoneExists = $normalizedPhone !== null && User::query()
            ->whereNotNull('phone')
            ->get(['id', 'phone'])
            ->contains(fn (User $user): bool => Phone::normalize((string) $user->phone) === $normalizedPhone);

        if (! $emailExists && ! $phoneExists) {
            return false;
        }

        if ($emailExists) {
            $this->addError('email', __('A user with this e-mail already exists.'));
        }

        if ($phoneExists) {
            $this->addError('phone', __('A user with this phone number already exists.'));
        }

        $this->addError('registerCustomer', __('An account with these contact details already exists. Please log in or use another e-mail and phone number.'));

        return true;
    }

    private function clearDeliveryWarehouse(): void
    {
        $this->deliveryWarehouseRef = '';
        $this->deliveryWarehouseName = '';
    }

    private function clearDeliveryAddress(): void
    {
        $this->deliveryStreet = '';
        $this->deliveryHouse = '';
        $this->deliveryApartment = '';
    }

    private function selectedShippingMethodRequiresNovaPoshta(Collection $shippingMethods): bool
    {
        return $shippingMethods
            ->firstWhere('id', (int) $this->selectedShippingMethod)
            ?->isNovaPoshta() ?? false;
    }

    private function setDefaultCheckoutSelections(): void
    {
        $this->selectedShippingMethod = ShippingMethod::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->value('id');

        $this->selectedPaymentMethod = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->value('id');
    }

    private function resolveUserShippingMethodId(User $user): ?int
    {
        $preferredShippingMethodId = (int) ($user->delivery_shipping_method_id ?? 0);

        if ($preferredShippingMethodId < 1) {
            return $this->selectedShippingMethod;
        }

        $exists = ShippingMethod::query()
            ->whereKey($preferredShippingMethodId)
            ->where('is_active', true)
            ->exists();

        return $exists ? $preferredShippingMethodId : $this->selectedShippingMethod;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim($fullName), 2, PREG_SPLIT_NO_EMPTY) ?: [];

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    private function checkoutRouteName(): string
    {
        return Locales::isDefault($this->locale)
            ? 'checkout.index'
            : 'localized.checkout.index';
    }

    private function checkoutRouteParameters(): array
    {
        return Locales::isDefault($this->locale)
            ? []
            : ['locale' => $this->locale];
    }

    private function thankYouRouteName(): string
    {
        return Locales::isDefault($this->locale)
            ? 'thank-you.show'
            : 'localized.thank-you.show';
    }

    private function getProgressClass(float $progress): string
    {
        if ($progress >= 100) {
            return 'w-full';
        } elseif ($progress >= 90) {
            return 'w-[90%]';
        } elseif ($progress >= 80) {
            return 'w-[80%]';
        } elseif ($progress >= 70) {
            return 'w-[70%]';
        } elseif ($progress >= 60) {
            return 'w-[60%]';
        } elseif ($progress >= 50) {
            return 'w-1/2';
        } elseif ($progress >= 40) {
            return 'w-[40%]';
        } elseif ($progress >= 30) {
            return 'w-[30%]';
        } elseif ($progress >= 20) {
            return 'w-[20%]';
        } elseif ($progress >= 10) {
            return 'w-[10%]';
        }

        return 'w-0';
    }
}

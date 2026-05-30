<?php

namespace Commero\Livewire;

use Commero\Models\Product;
use Commero\Support\Phone;
use Commero\Services\OrderPlacementService;
use Commero\Support\Locales;
use Closure;
use Illuminate\View\View;
use Livewire\Component;

class ProductQuickOrderForm extends Component
{
    public int $productId;

    public int $quantity = 1;

    public string $phone = '';

    public string $locale;

    public string $variant = 'desktop';

    public function mount(int $productId, ?string $locale = null, string $variant = 'desktop'): void
    {
        $this->productId = $productId;
        $this->locale = Locales::resolve($locale);
        $this->variant = $variant;
        $this->phone = trim((string) (auth()->user()?->phone ?? ''));
    }

    public function submit(OrderPlacementService $orderPlacementService): void
    {
        $validated = $this->validate([
            'phone' => $this->ukrainianPhoneRules(),
            'quantity' => ['required', 'integer', 'min:1'],
        ], [], [
            'phone' => __('Contact phone'),
            'quantity' => __('Quantity'),
        ]);

        $product = Product::query()->findOrFail($this->productId);

        $order = $orderPlacementService->placeQuickOrder(
            $product,
            (int) $validated['quantity'],
            Phone::normalize($validated['phone']) ?? '',
        );

        $this->redirectRoute(
            $this->thankYouRouteName(),
            array_merge($this->routeParameters(), ['orderNumber' => $order->id]),
            navigate: true
        );
    }

    public function render(): View
    {
        return view('livewire.product-quick-order-form');
    }

    private function ukrainianPhoneRules(): array
    {
        return [
            'required',
            'string',
            'max:255',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! Phone::isValidUkrainian((string) $value)) {
                    $fail(__('Enter a valid Ukrainian phone number.'));
                }
            },
        ];
    }

    private function thankYouRouteName(): string
    {
        return Locales::isDefault($this->locale)
            ? 'thank-you.show'
            : 'localized.thank-you.show';
    }

    private function routeParameters(): array
    {
        return Locales::isDefault($this->locale)
            ? []
            : ['locale' => $this->locale];
    }
}

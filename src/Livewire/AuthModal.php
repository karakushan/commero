<?php

namespace Commero\Livewire;

use Commero\Models\User;
use Commero\Support\Phone;
use Commero\Services\WishlistService;
use Commero\Support\Locales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class AuthModal extends Component
{
    public bool $isOpen = false;

    public string $modalView = 'auth';

    public string $activeTab = 'login';

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public string $registerFirstName = '';

    public string $registerLastName = '';

    public string $registerEmail = '';

    public string $registerPhone = '';

    public string $registerPassword = '';

    public string $registerPasswordConfirmation = '';

    public string $recoveryEmail = '';

    public bool $recoveryLinkSent = false;

    public string $redirectTo = '';

    public function boot(WishlistService $wishlistService): void
    {
        $this->wishlistService = $wishlistService;
    }

    public function mount(): void
    {
        $this->redirectTo = $this->resolveRedirectUrl();
    }

    public function open(string $tab = 'login', ?string $redirectTo = null): void
    {
        $this->modalView = 'auth';
        $this->activeTab = $tab;
        $this->isOpen = true;
        $this->redirectTo = $this->resolveRedirectUrl($redirectTo);
        $this->resetValidation();
        $this->resetErrorBag();
        $this->recoveryLinkSent = false;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->modalView = 'auth';
        $this->password = '';
        $this->remember = false;
        $this->registerPassword = '';
        $this->registerPasswordConfirmation = '';
        $this->recoveryEmail = '';
        $this->recoveryLinkSent = false;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function openRecovery(): void
    {
        $this->modalView = 'recovery';
        $this->isOpen = true;
        $this->recoveryLinkSent = false;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function authenticate(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $email = mb_strtolower(trim($credentials['email']));
        $this->email = $email;

        if (! Auth::attempt([
            'email' => $email,
            'password' => $credentials['password'],
        ], $credentials['remember'])) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        session()->regenerate();
        $this->wishlistService->mergeSessionToUser(Auth::user());

        $this->close();

        $this->redirect($this->resolveRedirectUrl($this->redirectTo), navigate: true);
    }

    public function sendPasswordResetLink(): void
    {
        $validated = $this->validate([
            'recoveryEmail' => ['required', 'email'],
        ], [], [
            'recoveryEmail' => __('Email'),
        ]);

        $status = Password::sendResetLink([
            'email' => $validated['recoveryEmail'],
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'recoveryEmail' => __($status),
            ]);
        }

        $this->recoveryLinkSent = true;
        $this->resetErrorBag();
    }

    public function register(): void
    {
        $validated = $this->validate([
            'registerFirstName' => ['required', 'string', 'max:255'],
            'registerLastName' => ['required', 'string', 'max:255'],
            'registerEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'registerPhone' => $this->ukrainianPhoneRules(),
            'registerPassword' => ['required', 'string', 'min:8', 'same:registerPasswordConfirmation'],
            'registerPasswordConfirmation' => ['required', 'string', 'min:8'],
        ], [
            'registerPassword.same' => __('The password confirmation does not match.'),
        ], [
            'registerFirstName' => __('First name'),
            'registerLastName' => __('Last name'),
            'registerEmail' => __('Email'),
            'registerPhone' => __('Phone'),
            'registerPassword' => __('Password'),
            'registerPasswordConfirmation' => __('Repeat password'),
        ]);

        if ($this->phoneExists($validated['registerPhone'])) {
            throw ValidationException::withMessages([
                'registerPhone' => __('A user with this phone number already exists.'),
            ]);
        }

        $user = User::query()->create([
            'name' => trim($validated['registerFirstName'].' '.$validated['registerLastName']),
            'first_name' => trim($validated['registerFirstName']),
            'last_name' => trim($validated['registerLastName']),
            'email' => mb_strtolower(trim($validated['registerEmail'])),
            'phone' => Phone::normalize($validated['registerPhone']),
            'password' => Hash::make($validated['registerPassword']),
        ]);

        Role::findOrCreate('buyer', 'web');
        $user->assignRole('buyer');

        Auth::login($user);
        session()->regenerate();
        $this->wishlistService->mergeSessionToUser($user);

        $this->close();

        $this->redirect($this->resolveRedirectUrl($this->redirectTo), navigate: true);
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        return view('livewire.auth-modal', [
            'privacyPolicyUrl' => Locales::isDefault($locale)
                ? route('privacy.policy')
                : route('localized.privacy.policy', ['locale' => $locale]),
        ]);
    }

    private function ukrainianPhoneRules(): array
    {
        return [
            'required',
            'string',
            'max:255',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! Phone::isValidUkrainian((string) $value)) {
                    $fail(__('Enter a valid Ukrainian phone number.'));
                }
            },
        ];
    }

    private function phoneExists(string $phone): bool
    {
        $normalizedPhone = Phone::normalize($phone);

        if ($normalizedPhone === null) {
            return false;
        }

        return User::query()
            ->whereNotNull('phone')
            ->get(['id', 'phone'])
            ->contains(fn (User $user): bool => Phone::normalize((string) $user->phone) === $normalizedPhone);
    }

    private function resolveRedirectUrl(?string $candidate = null): string
    {
        $urls = [
            $candidate,
            request()->headers->get('referer'),
            $this->redirectTo,
            request()->fullUrl(),
            url('/'),
        ];

        foreach ($urls as $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            if (str_contains($url, '/livewire') && str_contains($url, '/update')) {
                continue;
            }

            return $url;
        }

        return url('/');
    }

    protected WishlistService $wishlistService;
}

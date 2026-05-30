<?php

namespace Commero\Livewire;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class ResetPasswordPage extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $isCompleted = false;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $validated = $this->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string', 'min:8'],
        ], [
            'password.same' => __('The password confirmation does not match.'),
        ], [
            'email' => __('Email'),
            'password' => __('Password'),
            'passwordConfirmation' => __('Repeat password'),
        ]);

        $status = Password::reset([
            'token' => $validated['token'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'password_confirmation' => $validated['passwordConfirmation'],
        ], function ($user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        $this->isCompleted = true;
    }

    public function render(): View
    {
        return view('livewire.reset-password-page')
            ->layout('shophats::layouts.base', [
                'title' => __('Reset password'),
            ]);
    }
}

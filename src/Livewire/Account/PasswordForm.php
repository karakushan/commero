<?php

namespace Commero\Livewire\Account;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Component;

class PasswordForm extends Component
{
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public bool $saved = false;

    public function save(): void
    {
        $validated = $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8', 'different:currentPassword', 'same:newPasswordConfirmation'],
            'newPasswordConfirmation' => ['required', 'string', 'min:8'],
        ], [
            'newPassword.same' => __('The password confirmation does not match.'),
        ], [
            'currentPassword' => __('Old Password'),
            'newPassword' => __('New Password'),
            'newPasswordConfirmation' => __('Confirm New Password'),
        ]);

        $user = Auth::user();

        if (! $user || ! Hash::check($validated['currentPassword'], $user->password)) {
            $this->addError('currentPassword', __('The provided password is incorrect.'));

            return;
        }

        $user->forceFill([
            'password' => Hash::make($validated['newPassword']),
        ])->save();

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->resetValidation();
        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.account.password-form');
    }
}

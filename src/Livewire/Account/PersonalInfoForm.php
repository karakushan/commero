<?php

namespace Commero\Livewire\Account;

use Commero\Models\User;
use Commero\Support\Phone;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class PersonalInfoForm extends Component
{
    public User $user;

    public string $firstName = '';

    public string $lastName = '';

    public string $phone = '';

    public string $email = '';

    public ?string $gender = null;

    public string $birthday = '';

    public bool $saved = false;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->firstName = (string) ($user->first_name ?? '');
        $this->lastName = (string) ($user->last_name ?? '');
        $this->phone = (string) ($user->phone ?? '');
        $this->email = (string) ($user->email ?? '');
        $this->gender = $user->gender ?: null;
        $this->birthday = $user->birthday?->format('d/m/Y') ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'phone' => $this->ukrainianPhoneRules(),
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birthday' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || trim((string) $value) === '') {
                    return;
                }

                try {
                    Carbon::createFromFormat('d/m/Y', trim((string) $value));
                } catch (\Throwable) {
                    $fail(__('Enter a valid date in DD/MM/YYYY format.'));
                }
            }],
        ], [], [
            'firstName' => __('First Name'),
            'lastName' => __('Last Name'),
            'phone' => __('Phone'),
            'email' => __('E-mail'),
            'gender' => __('Gender:'),
            'birthday' => __('Birthday:'),
        ]);

        $normalizedPhone = Phone::normalize($validated['phone']);
        $currentNormalizedPhone = Phone::normalize((string) $this->user->phone);

        if ($normalizedPhone !== $currentNormalizedPhone && $this->phoneExistsForAnotherUser($validated['phone'])) {
            $this->addError('phone', __('A user with this phone number already exists.'));

            return;
        }

        $birthday = trim((string) $validated['birthday']) === ''
            ? null
            : Carbon::createFromFormat('d/m/Y', trim((string) $validated['birthday']))->format('Y-m-d');

        $this->user->forceFill([
            'first_name' => trim($validated['firstName']),
            'last_name' => trim($validated['lastName']),
            'phone' => Phone::normalize($validated['phone']),
            'email' => mb_strtolower(trim($validated['email'])),
            'gender' => $validated['gender'] ?: null,
            'birthday' => $birthday,
        ])->save();

        $this->user->refresh();
        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.account.personal-info-form');
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

    private function phoneExistsForAnotherUser(string $phone): bool
    {
        $normalizedPhone = Phone::normalize($phone);

        if ($normalizedPhone === null) {
            return false;
        }

        return User::query()
            ->whereKeyNot($this->user->id)
            ->whereNotNull('phone')
            ->get(['id', 'phone'])
            ->contains(fn (User $user): bool => Phone::normalize((string) $user->phone) === $normalizedPhone);
    }
}

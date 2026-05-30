<?php

namespace Commero\Livewire;

use Commero\Services\MarketingLeadService;
use Commero\Support\Mail\OutboundMailStatus;
use Commero\Support\Phone;
use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\View\View;
use Livewire\Component;

class ContactForm extends Component
{
    public string $state = 'form';
    public bool $mailUnavailable = false;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $message = '';

    public function submit(MarketingLeadService $marketingLeadService): void
    {
        $this->mailUnavailable = false;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => $this->ukrainianPhoneRules(),
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ], [], [
            'name' => __('Name'),
            'phone' => __('Phone'),
            'email' => __('E-mail'),
            'message' => __('Message'),
        ]);

        $payload = [
            'name' => trim($validated['name']),
            'phone' => Phone::normalize($validated['phone']) ?? '',
            'email' => mb_strtolower(trim($validated['email'])),
            'message' => trim((string) ($validated['message'] ?? '')),
            'locale' => app()->getLocale(),
        ];

        try {
            $marketingLeadService->create([
                'type' => 'contact_form',
                'subject' => __('Contact form'),
                'name' => $payload['name'],
                'phone' => $payload['phone'],
                'email' => $payload['email'],
                'message' => $payload['message'],
                'locale' => $payload['locale'],
                'form_data' => [
                    'name' => $payload['name'],
                    'phone' => $payload['phone'],
                    'email' => $payload['email'],
                    'message' => $payload['message'],
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Contact form processing failed.', $payload + [
                'error' => $exception->getMessage(),
            ]);

            $this->addError('form', __('Unable to send your message right now. Please try again later.'));

            return;
        }

        Log::info('Contact form submitted.', $payload);

        $this->mailUnavailable = ! OutboundMailStatus::isConfigured();
        $this->state = 'success';
        $this->resetValidation();
        $this->resetErrorBag();
        $this->reset(['name', 'phone', 'email', 'message']);
    }

    public function render(): View
    {
        return view('livewire.contact-form');
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
}

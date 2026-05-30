<?php

namespace Commero\Livewire;

use Commero\Services\MarketingLeadService;
use Commero\Support\Mail\OutboundMailStatus;
use Commero\Support\Phone;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\View\View;
use Livewire\Component;

class CallbackModal extends Component
{
    public bool $isOpen = false;

    public string $state = 'form';
    public bool $mailUnavailable = false;

    public string $name = '';

    public string $phone = '';

    public function open(): void
    {
        $this->state = 'form';
        $this->mailUnavailable = false;
        $this->isOpen = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function close(): void
    {
        $this->reset(['isOpen', 'state', 'name', 'phone', 'mailUnavailable']);
        $this->state = 'form';
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function submit(MarketingLeadService $marketingLeadService): void
    {
        $this->mailUnavailable = false;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => $this->ukrainianPhoneRules(),
        ], [], [
            'name' => __('Name'),
            'phone' => __('Phone'),
        ]);

        $payload = [
            'name' => trim($validated['name']),
            'phone' => Phone::normalize($validated['phone']),
            'locale' => app()->getLocale(),
        ];

        try {
            $marketingLeadService->create([
                'type' => 'callback',
                'subject' => __('Callback request'),
                'name' => $payload['name'],
                'phone' => $payload['phone'],
                'locale' => $payload['locale'],
                'form_data' => [
                    'name' => $payload['name'],
                    'phone' => $payload['phone'],
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Callback request processing failed.', $payload + [
                'error' => $exception->getMessage(),
            ]);

            $this->addError('form', __('Unable to send your message right now. Please try again later.'));

            return;
        }

        Log::info('Callback request submitted.', $payload);

        $this->mailUnavailable = ! OutboundMailStatus::isConfigured();
        $this->state = 'success';
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.callback-modal');
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
}

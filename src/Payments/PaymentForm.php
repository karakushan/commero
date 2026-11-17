<?php

namespace Commero\Payments;

/**
 * What the payment page shows: a form to the gateway checkout and/or a message.
 * A form without action (e.g. Monobank installments) only shows the message — the customer confirms elsewhere.
 */
final readonly class PaymentForm
{
    /**
     * @param  array<string, string>  $fields
     */
    public function __construct(
        public string $action,
        public array $fields = [],
        public string $method = 'POST',
        public ?string $message = null,
        public bool $awaitingConfirmation = false,
    ) {}

    public static function message(string $message, bool $awaitingConfirmation = true): self
    {
        return new self(action: '', message: $message, awaitingConfirmation: $awaitingConfirmation);
    }

    public function hasAction(): bool
    {
        return $this->action !== '';
    }
}

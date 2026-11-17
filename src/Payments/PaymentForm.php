<?php

namespace Commero\Payments;

/**
 * Auto-submittable form to the payment gateway checkout.
 */
final readonly class PaymentForm
{
    /**
     * @param  array<string, string>  $fields
     */
    public function __construct(
        public string $action,
        public array $fields,
        public string $method = 'POST',
    ) {}
}

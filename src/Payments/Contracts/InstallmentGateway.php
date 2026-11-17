<?php

namespace Commero\Payments\Contracts;

/**
 * Payment in parts: the number of payments is chosen by the customer (orders.payment_parts).
 */
interface InstallmentGateway
{
    public function minParts(): int;

    public function maxParts(): int;
}

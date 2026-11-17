<?php

namespace Commero\Payments\Contracts;

use Commero\Models\Order;

/**
 * Payment in parts: the number of payments is chosen by the customer (orders.payment_parts).
 */
interface InstallmentGateway
{
    public function minParts(): int;

    public function maxParts(): int;

    /** Maximum for an order: maxParts() capped by the order limit (payment_payload "_max_parts"). */
    public function maxPartsFor(Order $order): int;
}

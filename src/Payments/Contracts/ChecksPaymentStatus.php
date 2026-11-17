<?php

namespace Commero\Payments\Contracts;

use Commero\Models\Order;

/**
 * Gateways that do not notify about every state change; the payment page asks for the current state.
 */
interface ChecksPaymentStatus
{
    public function refreshStatus(Order $order): void;
}

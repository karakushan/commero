<?php

namespace Commero\Payments\Contracts;

use Commero\Models\Order;

/**
 * Gateways where the customer's approval only reserves the money (payment_status "held"):
 * the store confirms the purchase after handing over the goods, or cancels it.
 */
interface ConfirmsPayments
{
    public function confirm(Order $order): void;

    public function cancel(Order $order): void;
}

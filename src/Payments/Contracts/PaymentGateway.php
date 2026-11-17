<?php

namespace Commero\Payments\Contracts;

use Commero\Models\Order;
use Commero\Payments\PaymentException;
use Commero\Payments\PaymentForm;
use Illuminate\Http\Request;

/**
 * Online payment gateway bound to a payment method code (config "commero.payments.gateways").
 */
interface PaymentGateway
{
    /** Gateway key used in the callback URL (/payment/{gateway}/callback). */
    public function key(): string;

    public function label(): string;

    /** Credentials are filled in, so orders can be sent to the payment page. */
    public function isConfigured(): bool;

    /**
     * Form that sends the customer to the gateway checkout (or a message when the customer confirms elsewhere).
     *
     * @throws PaymentException when the gateway refuses to start the payment
     */
    public function checkoutForm(Order $order, string $resultUrl): ?PaymentForm;

    /**
     * Verifies the gateway server-to-server notification and updates the order.
     * Returns the order the notification belongs to, or null when the request is not valid.
     */
    public function handleCallback(Request $request): ?Order;
}

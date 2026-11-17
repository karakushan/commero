<?php

namespace Commero\Payments;

use Commero\Models\Order;
use Commero\Models\OrderStatus;
use Commero\Payments\Contracts\PaymentGateway;
use Commero\Services\OrderNotificationService;
use Commero\Support\Locales;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    private array $resolved = [];

    /**
     * Gateway for a payment method code, e.g. "liqpay".
     */
    public function forPaymentMethod(?string $paymentMethodCode): ?PaymentGateway
    {
        $class = config('commero.payments.gateways.'.$paymentMethodCode);

        if (! filled($paymentMethodCode) || ! is_string($class) || ! class_exists($class)) {
            return null;
        }

        return $this->resolved[$paymentMethodCode] ??= app($class);
    }

    public function forKey(string $key): ?PaymentGateway
    {
        foreach (array_keys((array) config('commero.payments.gateways', [])) as $code) {
            $gateway = $this->forPaymentMethod((string) $code);

            if ($gateway?->key() === $key) {
                return $gateway;
            }
        }

        return null;
    }

    public function forOrder(Order $order): ?PaymentGateway
    {
        return $this->forPaymentMethod($order->payment_method_code);
    }

    /**
     * The order is paid online and still waits for the payment.
     */
    public function requiresPayment(Order $order): bool
    {
        $gateway = $this->forOrder($order);

        return $gateway !== null
            && $gateway->isConfigured()
            && $order->payment_status !== PaymentStatus::PAID;
    }

    /**
     * Signed link to the payment page, or null when the order has no online payment.
     */
    public function paymentUrl(Order $order, ?string $locale = null, bool $autoSubmit = false): ?string
    {
        if (! $this->requiresPayment($order)) {
            return null;
        }

        return $this->signedPaymentUrl($order, $locale, $autoSubmit);
    }

    /**
     * @param  bool  $autoSubmit  the page submits the gateway form right away (signed "pay=1")
     */
    public function signedPaymentUrl(Order $order, ?string $locale = null, bool $autoSubmit = false): string
    {
        $locale = Locales::resolve($locale ?? $order->locale);
        $expiresAt = now()->addDays((int) config('commero.payments.link_ttl_days', 30));
        $parameters = array_filter(['order' => $order->id, 'pay' => $autoSubmit ? 1 : null]);

        return Locales::isDefault($locale)
            ? URL::temporarySignedRoute('payment.show', $expiresAt, $parameters)
            : URL::temporarySignedRoute('localized.payment.show', $expiresAt, ['locale' => $locale] + $parameters);
    }

    /**
     * Marks the order as waiting for an online payment (called right after checkout).
     */
    public function markPending(Order $order): void
    {
        if ($this->forOrder($order) === null || filled($order->payment_status)) {
            return;
        }

        $order->forceFill(['payment_status' => PaymentStatus::PENDING])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markPaid(Order $order, ?string $reference, array $payload = []): void
    {
        if ($order->payment_status === PaymentStatus::PAID) {
            return;
        }

        DB::transaction(function () use ($order, $reference, $payload): void {
            $attributes = [
                'payment_status' => PaymentStatus::PAID,
                'paid_at' => now(),
                'payment_reference' => $reference ?: $order->payment_reference,
                'payment_payload' => $this->mergePayload($order, $payload),
            ];

            $paidStatus = (string) config('commero.payments.paid_order_status', 'paid');

            if ($paidStatus !== '' && OrderStatus::query()->where('code', $paidStatus)->exists()) {
                $attributes['status'] = $paidStatus;
            }

            $order->forceFill($attributes)->save();
        });

        DB::afterCommit(fn () => app(OrderNotificationService::class)->notifyAboutPaidOrder($order));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markStatus(Order $order, string $paymentStatus, ?string $reference, array $payload = []): void
    {
        if ($order->payment_status === PaymentStatus::PAID && $paymentStatus !== PaymentStatus::REFUNDED) {
            return;
        }

        $wasHeld = $order->payment_status === PaymentStatus::HELD;

        // Late or polled "in progress" states never move an approved payment back.
        if ($wasHeld && in_array($paymentStatus, [PaymentStatus::PENDING, PaymentStatus::PROCESSING], true)) {
            return;
        }

        $order->forceFill([
            'payment_status' => $paymentStatus,
            'payment_reference' => $reference ?: $order->payment_reference,
            'payment_payload' => $this->mergePayload($order, $payload),
        ])->save();

        // The customer approved a payment in parts: the store has to confirm it.
        if ($paymentStatus === PaymentStatus::HELD && ! $wasHeld) {
            DB::afterCommit(fn () => app(OrderNotificationService::class)->notifyAboutPaidOrder($order));
        }
    }

    /**
     * Gateway data kept with the order (application ids, tokens…); keys starting with "_" are not
     * overwritten by notifications.
     *
     * @param  array<string, mixed>  $data
     */
    public function rememberPaymentData(Order $order, array $data, ?string $reference = null): void
    {
        $order->forceFill([
            'payment_payload' => $this->mergePayload($order, $data),
            'payment_reference' => $reference ?: $order->payment_reference,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function mergePayload(Order $order, array $payload): ?array
    {
        $current = (array) ($order->payment_payload ?? []);

        if ($payload === []) {
            return $current ?: null;
        }

        return array_merge($current, $payload);
    }
}

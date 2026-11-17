<?php

namespace Commero\Payments\LiqPay;

use Commero\Models\Order;
use Commero\Models\SiteSetting;
use Commero\Payments\Contracts\PaymentGateway;
use Commero\Payments\PaymentForm;
use Commero\Payments\PaymentGatewayManager;
use Commero\Payments\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * LiqPay Checkout (API v3): https://www.liqpay.ua/doc/api/internet_acquiring/checkout
 * Keys and sandbox mode are stored in site settings (admin: Settings → Payments).
 */
class LiqPayGateway implements PaymentGateway
{
    /** LiqPay statuses that mean the money is received or guaranteed. */
    public const SUCCESS_STATUSES = ['success', 'wait_accept', 'wait_compensation'];

    public const PROCESSING_STATUSES = ['processing', 'prepared', 'wait_secure', 'wait_lc', 'wait_reserve', 'hold_wait', 'cash_wait', 'invoice_wait', '3ds_verify', 'otp_verify', 'cvv_verify', 'sender_verify', 'receiver_verify', 'phone_verify', 'ivr_verify', 'pin_verify', 'captcha_verify', 'password_verify', 'senderapp_verify'];

    public const FAILURE_STATUSES = ['failure', 'error'];

    public const REFUND_STATUSES = ['reversed'];

    public function __construct(private readonly PaymentGatewayManager $payments) {}

    public function key(): string
    {
        return 'liqpay';
    }

    public function label(): string
    {
        return 'LiqPay';
    }

    public function isConfigured(): bool
    {
        return filled($this->publicKey()) && filled($this->privateKey());
    }

    public function isSandbox(): bool
    {
        return (bool) $this->settings()?->liqpay_sandbox;
    }

    public function checkoutForm(Order $order, string $resultUrl): PaymentForm
    {
        $params = array_filter([
            'version' => 3,
            'public_key' => $this->publicKey(),
            'action' => 'pay',
            'amount' => round((float) $order->total_amount, 2),
            'currency' => (string) config('commero.payments.liqpay.currency', 'UAH'),
            'description' => __('commero::checkout.payment.description', [
                'site' => (string) ($this->settings()?->site_name ?: config('app.name')),
                'number' => $order->number,
            ], $order->locale ?: null),
            'order_id' => $this->liqPayOrderId($order),
            'language' => $this->language($order->locale),
            'result_url' => $resultUrl,
            'server_url' => route('payment.callback', ['gateway' => $this->key()]),
            'sandbox' => $this->isSandbox() ? 1 : null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        $data = $this->encode($params);

        return new PaymentForm(
            action: (string) config('commero.payments.liqpay.checkout_url', 'https://www.liqpay.ua/api/3/checkout'),
            fields: [
                'data' => $data,
                'signature' => $this->signature($data),
            ],
        );
    }

    public function handleCallback(Request $request): ?Order
    {
        $data = (string) $request->input('data', '');
        $signature = (string) $request->input('signature', '');

        if ($data === '' || $signature === '' || ! $this->isConfigured() || ! hash_equals($this->signature($data), $signature)) {
            Log::warning('LiqPay callback rejected: invalid signature.');

            return null;
        }

        $payload = json_decode((string) base64_decode($data, true), true);

        if (! is_array($payload)) {
            return null;
        }

        $order = $this->findOrder((string) ($payload['order_id'] ?? ''));

        if (! $order instanceof Order) {
            Log::warning('LiqPay callback for unknown order.', ['order_id' => $payload['order_id'] ?? null]);

            return null;
        }

        $status = (string) ($payload['status'] ?? '');
        $reference = isset($payload['payment_id']) ? (string) $payload['payment_id'] : null;

        if (in_array($status, self::SUCCESS_STATUSES, true) || ($status === 'sandbox' && $this->isSandbox())) {
            if (! $this->amountMatches($order, $payload)) {
                Log::warning('LiqPay callback amount mismatch.', ['order_id' => $order->id, 'amount' => $payload['amount'] ?? null]);
                $this->payments->markStatus($order, PaymentStatus::FAILED, $reference, $payload);

                return $order;
            }

            $this->payments->markPaid($order, $reference, $payload);
        } elseif (in_array($status, self::REFUND_STATUSES, true)) {
            $this->payments->markStatus($order, PaymentStatus::REFUNDED, $reference, $payload);
        } elseif (in_array($status, self::FAILURE_STATUSES, true)) {
            $this->payments->markStatus($order, PaymentStatus::FAILED, $reference, $payload);
        } elseif (in_array($status, self::PROCESSING_STATUSES, true)) {
            $this->payments->markStatus($order, PaymentStatus::PROCESSING, $reference, $payload);
        }

        return $order;
    }

    /**
     * base64(sha1(private_key + data + private_key)).
     */
    public function signature(string $data): string
    {
        $privateKey = (string) $this->privateKey();

        return base64_encode(sha1($privateKey.$data.$privateKey, true));
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function encode(array $params): string
    {
        return base64_encode((string) json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * LiqPay requires a unique order_id per payment; the order number is used (numeric id as a fallback).
     */
    public function liqPayOrderId(Order $order): string
    {
        return (string) ($order->number ?: $order->id);
    }

    private function findOrder(string $liqPayOrderId): ?Order
    {
        if ($liqPayOrderId === '') {
            return null;
        }

        return Order::query()->where('number', $liqPayOrderId)->first()
            ?? (ctype_digit($liqPayOrderId) ? Order::query()->whereNull('number')->find((int) $liqPayOrderId) : null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function amountMatches(Order $order, array $payload): bool
    {
        if (! isset($payload['amount'])) {
            return true;
        }

        return abs((float) $payload['amount'] - (float) $order->total_amount) < 0.01;
    }

    private function language(?string $locale): string
    {
        $languages = (array) config('commero.payments.liqpay.languages', []);

        return (string) ($languages[$locale] ?? config('commero.payments.liqpay.default_language', 'uk'));
    }

    private function publicKey(): ?string
    {
        return $this->settings()?->liqpay_public_key;
    }

    private function privateKey(): ?string
    {
        return $this->settings()?->liqpay_private_key;
    }

    private function settings(): ?SiteSetting
    {
        return SiteSetting::query()->first();
    }
}

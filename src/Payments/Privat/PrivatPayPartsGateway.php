<?php

namespace Commero\Payments\Privat;

use Commero\Models\Order;
use Commero\Models\SiteSetting;
use Commero\Payments\Concerns\BuildsInstallmentProducts;
use Commero\Payments\Contracts\ChecksPaymentStatus;
use Commero\Payments\Contracts\ConfirmsPayments;
use Commero\Payments\Contracts\InstallmentGateway;
use Commero\Payments\Contracts\PaymentGateway;
use Commero\Payments\PaymentException;
use Commero\Payments\PaymentForm;
use Commero\Payments\PaymentGatewayManager;
use Commero\Payments\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * PrivatBank "Оплата частинами" (PayParts API v2, https://bw.gitbooks.io/api-oc/content/).
 * The order total is financed; the customer chooses the number of payments (orders.payment_parts).
 * Hold mode reserves the money: the store confirms the purchase from the admin order page.
 */
class PrivatPayPartsGateway implements ChecksPaymentStatus, ConfirmsPayments, InstallmentGateway, PaymentGateway
{
    use BuildsInstallmentProducts;

    public const PAID_STATES = ['SUCCESS'];

    public const HELD_STATES = ['LOCKED'];

    public const FAILED_STATES = ['FAIL', 'CANCELED', 'REJECTED'];

    public const PROCESSING_STATES = ['CLIENT_WAIT', 'OTP_WAITING', 'PP_CREATION'];

    public function __construct(private readonly PaymentGatewayManager $payments) {}

    public function key(): string
    {
        return 'privat-payparts';
    }

    public function label(): string
    {
        return __('commero::checkout.payment.privat_payparts');
    }

    public function isConfigured(): bool
    {
        return filled($this->storeId()) && filled($this->password());
    }

    public function minParts(): int
    {
        return (int) config('commero.payments.privat_payparts.min_parts', 2);
    }

    public function maxParts(): int
    {
        return (int) ($this->settings()?->privat_payparts_max_parts ?: config('commero.payments.privat_payparts.max_parts', 24));
    }

    public function isHoldMode(): bool
    {
        return (bool) ($this->settings()?->privat_payparts_hold ?? true);
    }

    public function checkoutForm(Order $order, string $resultUrl): ?PaymentForm
    {
        $application = (array) data_get($order->payment_payload, '_privat', []);
        $parts = $this->parts($order);
        $ttl = (int) config('commero.payments.privat_payparts.token_ttl_minutes', 15);

        $isFresh = filled($application['token'] ?? null)
            && (int) ($application['parts'] ?? 0) === $parts
            && (int) ($application['created_at'] ?? 0) > now()->subMinutes($ttl)->getTimestamp();

        if (! $isFresh) {
            $application = $this->createApplication($order, $parts, $resultUrl);
        }

        return new PaymentForm(
            action: $this->apiUrl('/payment'),
            fields: ['token' => (string) $application['token']],
            method: 'GET',
        );
    }

    public function handleCallback(Request $request): ?Order
    {
        $data = json_decode($request->getContent(), true);
        $data = is_array($data) ? $data : $request->all();

        $expected = $this->signature([
            $this->storeId(),
            $data['orderId'] ?? '',
            $data['paymentState'] ?? '',
            $data['message'] ?? '',
        ]);

        if (! $this->isConfigured() || ! is_string($data['signature'] ?? null) || ! hash_equals($expected, $data['signature'])) {
            Log::warning('PrivatBank PayParts callback rejected: invalid signature.');

            return null;
        }

        $order = $this->findOrder((string) ($data['orderId'] ?? ''));

        if (! $order instanceof Order) {
            Log::warning('PrivatBank PayParts callback for unknown order.', ['order_id' => $data['orderId'] ?? null]);

            return null;
        }

        $this->applyState($order, (string) ($data['paymentState'] ?? ''), (string) $data['orderId'], ['privat_callback' => $data]);

        return $order;
    }

    public function refreshStatus(Order $order): void
    {
        $privatOrderId = (string) data_get($order->payment_payload, '_privat.order_id', '');

        if ($privatOrderId === '' || in_array($order->payment_status, [PaymentStatus::PAID, PaymentStatus::FAILED, PaymentStatus::REFUNDED], true)) {
            return;
        }

        $response = $this->post('/payment/state', [
            'storeId' => $this->storeId(),
            'orderId' => $privatOrderId,
            'showRefund' => 'false',
            'signature' => $this->signature([$this->storeId(), $privatOrderId]),
        ]);

        if (($response['state'] ?? null) !== 'SUCCESS') {
            return;
        }

        $this->applyState($order, (string) ($response['paymentState'] ?? ''), $privatOrderId, ['privat_state' => $response]);
    }

    public function confirm(Order $order): void
    {
        $privatOrderId = $this->requirePrivatOrderId($order);
        $response = $this->post('/payment/confirm', [
            'storeIdentifier' => $this->storeId(),
            'orderId' => $privatOrderId,
            'signature' => $this->signature([$this->storeId(), $privatOrderId]),
        ]);

        if (($response['state'] ?? null) !== 'SUCCESS') {
            throw new PaymentException((string) ($response['message'] ?? __('commero::checkout.payment.gateway_error')));
        }

        $this->payments->markPaid($order, $privatOrderId, ['privat_confirm' => $response]);
    }

    public function cancel(Order $order): void
    {
        $privatOrderId = $this->requirePrivatOrderId($order);
        $response = $this->post('/payment/cancel', [
            'storeId' => $this->storeId(),
            'orderId' => $privatOrderId,
            'signature' => $this->signature([$this->storeId(), $privatOrderId]),
        ]);

        if (($response['state'] ?? null) !== 'SUCCESS') {
            throw new PaymentException((string) ($response['message'] ?? __('commero::checkout.payment.gateway_error')));
        }

        $this->payments->markStatus($order, PaymentStatus::FAILED, $privatOrderId, ['privat_cancel' => $response]);
    }

    /**
     * base64(sha1(password + values… + password)).
     *
     * @param  list<string|int|float|null>  $values
     */
    public function signature(array $values): string
    {
        $password = (string) $this->password();

        return base64_encode(sha1($password.implode('', array_map('strval', $values)).$password, true));
    }

    /**
     * @return array{order_id: string, token: string, parts: int, created_at: int}
     */
    private function createApplication(Order $order, int $parts, string $resultUrl): array
    {
        if (! $this->isConfigured()) {
            throw new PaymentException(__('commero::checkout.payment.unavailable'));
        }

        $privatOrderId = config('commero.payments.privat_payparts.order_prefix', '').$order->number.'-'.Str::lower(Str::random(6));
        $products = $this->installmentProducts($order);
        $amountCents = (int) round((float) $order->total_amount * 100);
        $merchantType = (string) config('commero.payments.privat_payparts.merchant_type', 'PP');
        $responseUrl = route('payment.callback', ['gateway' => $this->key()]);

        $productsString = collect($products)
            ->map(fn (array $product): string => $product['name'].$product['count'].(int) round($product['price'] * 100))
            ->implode('');

        $response = $this->post($this->isHoldMode() ? '/payment/hold' : '/payment/create', [
            'storeId' => $this->storeId(),
            'orderId' => $privatOrderId,
            'amount' => $amountCents / 100,
            'partsCount' => $parts,
            'merchantType' => $merchantType,
            'products' => $products,
            'responseUrl' => $responseUrl,
            'redirectUrl' => $resultUrl,
            'currency' => '980',
            'signature' => $this->signature([
                $this->storeId(), $privatOrderId, $amountCents, '980', $parts, $merchantType, $responseUrl, $resultUrl, $productsString,
            ]),
        ]);

        $responseSignature = $this->signature([
            $response['state'] ?? '', $response['storeId'] ?? '', $response['orderId'] ?? '', $response['message'] ?? '', $response['token'] ?? '',
        ]);

        if (($response['state'] ?? null) !== 'SUCCESS' || ! filled($response['token'] ?? null)
            || ! hash_equals($responseSignature, (string) ($response['signature'] ?? ''))) {
            Log::warning('PrivatBank PayParts application was not created.', ['order_id' => $order->id, 'response' => $response]);
            $this->payments->markStatus($order, PaymentStatus::FAILED, $privatOrderId, ['privat_create' => $response]);

            throw new PaymentException((string) (($response['message'] ?? null) ?: __('commero::checkout.payment.gateway_error')));
        }

        $application = [
            'order_id' => $privatOrderId,
            'token' => (string) $response['token'],
            'parts' => $parts,
            'created_at' => now()->getTimestamp(),
        ];

        $this->payments->rememberPaymentData($order, ['_privat' => $application], $privatOrderId);

        if ($order->payment_status === PaymentStatus::FAILED) {
            $this->payments->markStatus($order, PaymentStatus::PENDING, $privatOrderId);
        }

        return $application;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyState(Order $order, string $state, string $reference, array $payload): void
    {
        match (true) {
            in_array($state, self::PAID_STATES, true) => $this->payments->markPaid($order, $reference, $payload),
            in_array($state, self::HELD_STATES, true) => $this->payments->markStatus($order, PaymentStatus::HELD, $reference, $payload),
            in_array($state, self::FAILED_STATES, true) => $this->payments->markStatus($order, PaymentStatus::FAILED, $reference, $payload),
            in_array($state, self::PROCESSING_STATES, true) => $this->payments->markStatus($order, PaymentStatus::PROCESSING, $reference, $payload),
            default => null,
        };
    }

    private function parts(Order $order): int
    {
        return max($this->minParts(), min($this->maxPartsFor($order), (int) ($order->payment_parts ?: $this->minParts())));
    }

    private function findOrder(string $privatOrderId): ?Order
    {
        if ($privatOrderId === '') {
            return null;
        }

        $order = Order::query()->where('payment_reference', $privatOrderId)->latest('id')->first();

        if ($order) {
            return $order;
        }

        $number = Str::beforeLast(Str::after($privatOrderId, (string) config('commero.payments.privat_payparts.order_prefix', '')), '-');

        return $number !== '' ? Order::query()->where('number', $number)->first() : null;
    }

    private function requirePrivatOrderId(Order $order): string
    {
        $privatOrderId = (string) data_get($order->payment_payload, '_privat.order_id', $order->payment_reference);

        if ($privatOrderId === '') {
            throw new PaymentException(__('commero::checkout.payment.gateway_error'));
        }

        return $privatOrderId;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function post(string $path, array $body): array
    {
        try {
            $response = Http::acceptJson()->asJson()->timeout(30)->post($this->apiUrl($path), $body);

            return (array) ($response->json() ?? []);
        } catch (Throwable $exception) {
            Log::error('PrivatBank PayParts request failed.', ['path' => $path, 'error' => $exception->getMessage()]);

            throw new PaymentException(__('commero::checkout.payment.gateway_error'), previous: $exception);
        }
    }

    private function apiUrl(string $path): string
    {
        return rtrim((string) config('commero.payments.privat_payparts.api_url', 'https://payparts2.privatbank.ua/ipp/v2'), '/').$path;
    }

    private function storeId(): ?string
    {
        return $this->settings()?->privat_payparts_store_id;
    }

    private function password(): ?string
    {
        return $this->settings()?->privat_payparts_password;
    }

    private function settings(): ?SiteSetting
    {
        return SiteSetting::query()->first();
    }
}

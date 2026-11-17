<?php

namespace Commero\Payments\Monobank;

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
use Commero\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Monobank "Покупка частинами" (https://monobank.ua/api-docs/chast).
 * The application is created for the customer's phone; the customer approves it in the monobank app
 * (IN_PROCESS/WAITING_FOR_STORE_CONFIRM => payment_status "held") and the store confirms the purchase
 * from the admin order page (SUCCESS => "paid").
 */
class MonobankInstallmentsGateway implements ChecksPaymentStatus, ConfirmsPayments, InstallmentGateway, PaymentGateway
{
    use BuildsInstallmentProducts;

    public function __construct(private readonly PaymentGatewayManager $payments) {}

    public function key(): string
    {
        return 'monobank-parts';
    }

    public function label(): string
    {
        return __('commero::checkout.payment.monobank_installments');
    }

    public function isConfigured(): bool
    {
        return filled($this->storeId()) && filled($this->secret());
    }

    public function isSandbox(): bool
    {
        return (bool) $this->settings()?->monobank_parts_sandbox;
    }

    public function minParts(): int
    {
        return (int) config('commero.payments.monobank_installments.min_parts', 3);
    }

    public function maxParts(): int
    {
        return (int) ($this->settings()?->monobank_parts_max_parts ?: config('commero.payments.monobank_installments.max_parts', 25));
    }

    public function checkoutForm(Order $order, string $resultUrl): ?PaymentForm
    {
        if (! filled(data_get($order->payment_payload, '_monobank.order_id'))) {
            $this->createApplication($order);
        }

        return PaymentForm::message(__('commero::checkout.payment.monobank_confirm_in_app'));
    }

    public function handleCallback(Request $request): ?Order
    {
        $body = $request->getContent();
        $signature = (string) $request->header('signature', '');

        if (! $this->isConfigured() || $signature === '' || ! hash_equals($this->sign($body), $signature)) {
            Log::warning('Monobank installments callback rejected: invalid signature.');

            return null;
        }

        $data = json_decode($body, true);
        $monobankOrderId = (string) ($data['order_id'] ?? '');
        $order = $monobankOrderId !== '' ? Order::query()->where('payment_reference', $monobankOrderId)->latest('id')->first() : null;

        if (! $order instanceof Order) {
            Log::warning('Monobank installments callback for unknown order.', ['order_id' => $monobankOrderId]);

            return null;
        }

        $this->applyState($order, (array) $data, ['monobank_callback' => $data]);

        return $order;
    }

    public function refreshStatus(Order $order): void
    {
        $monobankOrderId = (string) data_get($order->payment_payload, '_monobank.order_id', '');

        if ($monobankOrderId === '' || in_array($order->payment_status, [PaymentStatus::PAID, PaymentStatus::FAILED, PaymentStatus::REFUNDED], true)) {
            return;
        }

        [$status, $response] = $this->post('/api/order/state', ['order_id' => $monobankOrderId]);

        if ($status === 200) {
            $this->applyState($order, $response, ['monobank_state' => $response]);
        }
    }

    public function confirm(Order $order): void
    {
        [$status, $response] = $this->post('/api/order/confirm', ['order_id' => $this->requireMonobankOrderId($order)]);

        if ($status !== 200) {
            throw new PaymentException((string) ($response['message'] ?? __('commero::checkout.payment.gateway_error')));
        }

        $this->applyState($order, $response, ['monobank_confirm' => $response]);
    }

    public function cancel(Order $order): void
    {
        [$status, $response] = $this->post('/api/order/reject', ['order_id' => $this->requireMonobankOrderId($order)]);

        if ($status !== 200) {
            throw new PaymentException((string) ($response['message'] ?? __('commero::checkout.payment.gateway_error')));
        }

        $this->applyState($order, $response, ['monobank_reject' => $response]);
    }

    /**
     * base64(HMAC-SHA256(body, secret)).
     */
    public function sign(string $body): string
    {
        return base64_encode(hash_hmac('sha256', $body, (string) $this->secret(), true));
    }

    private function createApplication(Order $order): void
    {
        if (! $this->isConfigured()) {
            throw new PaymentException(__('commero::checkout.payment.unavailable'));
        }

        $phone = Phone::normalize((string) $order->customer_phone);

        if ($phone !== null && strlen($phone) === 10 && str_starts_with($phone, '0')) {
            $phone = '38'.$phone;
        }

        if (! filled($phone)) {
            throw new PaymentException(__('commero::checkout.payment.phone_required'));
        }

        $invoice = array_filter([
            'date' => now()->format('Y-m-d'),
            'number' => (string) $order->number,
            'source' => 'INTERNET',
            'point_id' => config('commero.payments.monobank_installments.point_id'),
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        [$status, $response] = $this->post('/api/order/create', [
            'store_order_id' => (string) $order->number,
            'client_phone' => '+'.ltrim($phone, '+'),
            'total_sum' => round((float) $order->total_amount, 2),
            'invoice' => $invoice,
            'available_programs' => [[
                'available_parts_count' => range($this->minParts(), max($this->minParts(), $this->maxPartsFor($order))),
            ]],
            'products' => collect($this->installmentProducts($order))
                ->map(fn (array $product): array => ['name' => $product['name'], 'count' => $product['count'], 'sum' => $product['price']])
                ->all(),
            'result_callback' => route('payment.callback', ['gateway' => $this->key()]),
        ]);

        if (! in_array($status, [200, 201], true) || ! filled($response['order_id'] ?? null)) {
            Log::warning('Monobank installments application was not created.', ['order_id' => $order->id, 'status' => $status, 'response' => $response]);
            $this->payments->markStatus($order, PaymentStatus::FAILED, null, ['monobank_create' => $response]);

            throw new PaymentException((string) (($response['message'] ?? null) ?: __('commero::checkout.payment.gateway_error')));
        }

        $this->payments->rememberPaymentData($order, ['_monobank' => ['order_id' => (string) $response['order_id']]], (string) $response['order_id']);
        $this->payments->markStatus($order, PaymentStatus::PROCESSING, (string) $response['order_id']);
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $payload
     */
    private function applyState(Order $order, array $state, array $payload): void
    {
        $reference = (string) ($state['order_id'] ?? $order->payment_reference);
        $subState = (string) ($state['order_sub_state'] ?? '');

        match ((string) ($state['state'] ?? '')) {
            'SUCCESS' => $subState === 'RETURNED'
                ? $this->payments->markStatus($order, PaymentStatus::REFUNDED, $reference, $payload)
                : $this->payments->markPaid($order, $reference, $payload),
            'FAIL' => $this->payments->markStatus($order, PaymentStatus::FAILED, $reference, $payload),
            'IN_PROCESS' => $this->payments->markStatus(
                $order,
                $subState === 'WAITING_FOR_STORE_CONFIRM' ? PaymentStatus::HELD : PaymentStatus::PROCESSING,
                $reference,
                $payload,
            ),
            default => null,
        };
    }

    private function requireMonobankOrderId(Order $order): string
    {
        $monobankOrderId = (string) data_get($order->payment_payload, '_monobank.order_id', '');

        if ($monobankOrderId === '') {
            throw new PaymentException(__('commero::checkout.payment.gateway_error'));
        }

        return $monobankOrderId;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function post(string $path, array $body): array
    {
        $json = (string) json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'store-id' => (string) $this->storeId(),
                    'signature' => $this->sign($json),
                ])
                ->withBody($json, 'application/json')
                ->timeout(30)
                ->post($this->apiUrl().$path);

            return [$response->status(), (array) ($response->json() ?? [])];
        } catch (Throwable $exception) {
            Log::error('Monobank installments request failed.', ['path' => $path, 'error' => $exception->getMessage()]);

            throw new PaymentException(__('commero::checkout.payment.gateway_error'), previous: $exception);
        }
    }

    private function apiUrl(): string
    {
        $key = $this->isSandbox() ? 'sandbox_api_url' : 'api_url';

        return rtrim((string) config('commero.payments.monobank_installments.'.$key), '/');
    }

    private function storeId(): ?string
    {
        return $this->settings()?->monobank_parts_store_id;
    }

    private function secret(): ?string
    {
        return $this->settings()?->monobank_parts_secret;
    }

    private function settings(): ?SiteSetting
    {
        return SiteSetting::query()->first();
    }
}

# Online payments

Commero sends orders paid online to a gateway after checkout. A gateway is bound to a payment method
by its code (`payment_methods.code`) in `config('commero.payments.gateways')`:

```php
'payments' => [
    'gateways' => ['liqpay' => \Commero\Payments\LiqPay\LiqPayGateway::class],
    'paid_order_status' => 'paid', // order status after a successful payment (if it exists)
    'link_ttl_days' => 30,         // lifetime of the signed payment page link
],
```

## Flow

1. `OrderPlacementService::placeCheckoutOrder()` sets `orders.payment_status = pending` for gateway methods.
2. `PaymentGatewayManager::paymentUrl($order)` returns a signed link to `/payment/{order}/`
   (`/{locale}/payment/{order}/`), or `null` for offline methods. The Livewire checkout redirects there;
   custom checkouts should do the same instead of opening the thank-you page.
3. The payment page renders the theme view `pages/payment.blade.php` when it exists, otherwise
   `commero::payment.show`. Available variables: `$order`, `$gateway`, `$form` (`PaymentForm`: `action`, `fields`),
   `$isPaid`, `$paymentStatus`, `$amountLabel`, `$autoSubmit`. Partials `commero::payment.partials.message`
   and `commero::payment.partials.form` can be reused.
4. The gateway posts to `POST /payment/{gateway}/callback` (no CSRF). A valid notification updates
   `payment_status` (`pending`, `processing`, `paid`, `failed`, `refunded`), `paid_at`, `payment_reference`,
   `payment_payload`, switches the order to `paid_order_status` and notifies managers (`OrderPaidNotification`).

## LiqPay

Admin → Site settings → "Online payment (LiqPay)": public key, private key (stored encrypted) and sandbox mode.
`order_id` sent to LiqPay is the order number; `server_url` is `route('payment.callback', ['gateway' => 'liqpay'])`.
Successful statuses: `success`, `wait_accept`, `wait_compensation` (and `sandbox` only in sandbox mode).
The paid amount must match `orders.total_amount`.

## Custom gateways

Implement `Commero\Payments\Contracts\PaymentGateway` (`key`, `label`, `isConfigured`, `checkoutForm`,
`handleCallback`) and register it in `commero.payments.gateways` under the payment method code.
Use `PaymentGatewayManager::markPaid()` / `markStatus()` to update orders.

## Payment in parts

Two installment gateways implement `InstallmentGateway` (`minParts()`, `maxParts()`), `ConfirmsPayments`
(`confirm()`, `cancel()`) and `ChecksPaymentStatus` (`refreshStatus()`, called on every payment page view).
The number of payments is stored in `orders.payment_parts` — pass `paymentParts` in the validated checkout data;
`paymentMaxParts` caps the allowed number for the order (`maxPartsFor($order)`, e.g. the strictest product limit).

When the customer approves the application the order gets `payment_status = held`: managers are notified and the
order page in the admin shows "Confirm purchase in parts" / "Cancel application" (confirm after handing over the goods).
Late "in progress" states never move a held payment back.

### PrivatBank "Оплата частинами" (`privatbank_payparts`)

Site settings: store ID, password, hold mode (default on), maximum parts. Config `commero.payments.privat_payparts`
(`api_url`, `merchant_type` PP/II, `min_parts`, `max_parts`, `order_prefix`, `token_ttl_minutes`).
The payment page creates an application (`/payment/hold` or `/payment/create`) and shows a GET form to
`https://payparts2.privatbank.ua/ipp/v2/payment?token=…`; the token is reused for `token_ttl_minutes`.
PrivatBank order ids are `{order number}-{random}` (stored in `payment_reference`).
States: `SUCCESS` => paid, `LOCKED` => held, `FAIL`/`CANCELED`/`REJECTED` => failed, `CLIENT_WAIT`/`OTP_WAITING`/`PP_CREATION` => processing.
Callback: `POST /payment/privat-payparts/callback`.

### Monobank "Покупка частинами" (`monobank_installments`)

Site settings: store-id, secret key, test environment, maximum parts. Config `commero.payments.monobank_installments`.
The payment page creates the application (`/api/order/create`, `store_order_id` = order number, customer phone) once
and asks the customer to confirm it in the monobank app; the page reloads every `refresh_seconds` and polls `/api/order/state`.
`IN_PROCESS/WAITING_FOR_STORE_CONFIRM` => held, `SUCCESS` => paid (`RETURNED` => refunded), `FAIL/*` => failed.
Callback: `POST /payment/monobank-parts/callback` (HMAC-SHA256 `signature` header).

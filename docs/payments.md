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

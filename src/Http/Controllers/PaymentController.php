<?php

namespace Commero\Http\Controllers;

use Commero\Models\Currency;
use Commero\Models\Order;
use Commero\Payments\PaymentGatewayManager;
use Commero\Payments\PaymentStatus;
use Commero\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewContract;

class PaymentController extends Controller
{
    /**
     * Payment page of an order (signed link from checkout / order e-mail).
     * Theme view "shophats::pages.payment" is used when the theme provides it.
     */
    public function show(Request $request, PaymentGatewayManager $payments, string $param1, ?string $param2 = null): ViewContract
    {
        // Localized route: {locale}/payment/{order}; default locale: payment/{order}.
        [$locale, $orderId] = $param2 !== null ? [$param1, $param2] : [null, $param1];

        $locale = Locales::resolve($locale);
        App::setLocale($locale);

        $order = Order::query()->with('items')->findOrFail((int) $orderId);
        $gateway = $payments->forOrder($order);

        abort_if($gateway === null, 404);

        $isPaid = $order->payment_status === PaymentStatus::PAID;
        $form = ! $isPaid && $gateway->isConfigured()
            ? $gateway->checkoutForm($order, $payments->signedPaymentUrl($order, $locale))
            : null;

        $view = View::exists('shophats::pages.payment') ? 'shophats::pages.payment' : 'commero::payment.show';

        return view($view, [
            'locale' => $locale,
            'order' => $order,
            'gateway' => $gateway,
            'form' => $form,
            'isPaid' => $isPaid,
            'paymentStatus' => $order->payment_status ?: PaymentStatus::PENDING,
            // Order totals are stored in the shop base currency.
            'amountLabel' => format_money_amount($order->total_amount, Currency::getBaseSymbol()),
            'autoSubmit' => $form !== null && $request->boolean('pay'),
        ]);
    }

    /**
     * Server-to-server notification from the payment gateway (server_url).
     */
    public function callback(Request $request, PaymentGatewayManager $payments, string $gateway): Response
    {
        $paymentGateway = $payments->forKey($gateway);

        abort_if($paymentGateway === null, 404);

        $order = $paymentGateway->handleCallback($request);

        return response($order ? 'OK' : 'INVALID', $order ? 200 : 400);
    }
}

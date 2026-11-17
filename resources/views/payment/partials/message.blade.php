{{-- Payment status + gateway form. Variables: $order, $gateway, $form, $isPaid, $paymentStatus, $amountLabel, $autoSubmit --}}
@if ($isPaid)
    <h4>{{ __('commero::checkout.payment.paid_title', ['number' => $order->number]) }}</h4>
    <p>{{ __('commero::checkout.payment.paid_text') }}</p>
    <a href="{{ \Commero\Support\Locales::path('/', app()->getLocale()) }}">{{ __('commero::checkout.payment.back_home') }}</a>
@elseif ($form)
    <h4>{{ __('commero::checkout.payment.form_title', ['number' => $order->number, 'method' => $order->payment_method_name ?: $gateway->label()]) }}</h4>
    <p>{{ __('commero::checkout.payment.form_text', ['amount' => $amountLabel]) }}</p>
    @if ($paymentStatus === \Commero\Payments\PaymentStatus::PROCESSING)
        <p>{{ __('commero::checkout.payment.processing_text') }}</p>
    @elseif ($paymentStatus === \Commero\Payments\PaymentStatus::FAILED)
        <p>{{ __('commero::checkout.payment.failed_text') }}</p>
    @endif
    @include('commero::payment.partials.form', ['buttonClass' => $buttonClass ?? ''])
@else
    <p>{{ __('commero::checkout.payment.unavailable') }}</p>
@endif

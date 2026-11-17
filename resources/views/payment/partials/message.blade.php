{{-- Payment status + gateway form.
     Variables: $order, $gateway, $form, $error, $isPaid, $isHeld, $paymentStatus, $amountLabel, $autoSubmit, $refreshSeconds --}}
@if ($refreshSeconds ?? null)
    <meta http-equiv="refresh" content="{{ $refreshSeconds }}">
@endif
@if ($isPaid)
    <h4>{{ __('commero::checkout.payment.paid_title', ['number' => $order->number]) }}</h4>
    <p>{{ __('commero::checkout.payment.paid_text') }}</p>
    <a href="{{ \Commero\Support\Locales::path('/', app()->getLocale()) }}">{{ __('commero::checkout.payment.back_home') }}</a>
@elseif ($isHeld ?? false)
    <h4>{{ __('commero::checkout.payment.held_title', ['number' => $order->number]) }}</h4>
    <p>{{ __('commero::checkout.payment.held_text') }}</p>
    <a href="{{ \Commero\Support\Locales::path('/', app()->getLocale()) }}">{{ __('commero::checkout.payment.back_home') }}</a>
@elseif ($form)
    <h4>{{ __('commero::checkout.payment.form_title', ['number' => $order->number, 'method' => $order->payment_method_name ?: $gateway->label()]) }}</h4>
    <p>{{ $form->message ?: __('commero::checkout.payment.form_text', ['amount' => $amountLabel]) }}</p>
    @if ($paymentStatus === \Commero\Payments\PaymentStatus::FAILED)
        <p>{{ __('commero::checkout.payment.failed_text') }}</p>
    @endif
    @if ($form->hasAction())
        @include('commero::payment.partials.form', ['buttonClass' => $buttonClass ?? ''])
    @endif
@else
    <h4>{{ __('commero::checkout.payment.form_title', ['number' => $order->number, 'method' => $order->payment_method_name ?: $gateway->label()]) }}</h4>
    <p>{{ ($error ?? null) ?: __('commero::checkout.payment.unavailable') }}</p>
@endif

{{-- Form to the gateway checkout. Variables: $form (Commero\Payments\PaymentForm), $autoSubmit, $buttonClass --}}
<form method="{{ $form->method }}" action="{{ $form->action }}" accept-charset="utf-8" data-commero-payment-form
    @if ($autoSubmit) x-data x-init="$el.submit()" @endif>
    @foreach ($form->fields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <button type="submit" class="{{ $buttonClass ?? '' }}">{{ __('commero::checkout.payment.pay_button') }}</button>
</form>
@if ($autoSubmit)
    <noscript><p>{{ __('commero::checkout.payment.noscript') }}</p></noscript>
    <script>document.querySelector('[data-commero-payment-form]')?.submit();</script>
@endif

{{-- Fallback payment page; a theme can override it with "pages/payment.blade.php". --}}
@extends('shophats::layouts.base', [
    'title' => __('commero::checkout.payment.title'),
    'robots' => 'noindex, nofollow',
])

@section('content')
    <section class="py-10">
        <div class="mx-auto max-w-2xl px-4">
            <h1 class="mb-6 text-2xl font-semibold">{{ __('commero::checkout.payment.title') }}</h1>
            @include('commero::payment.partials.message')
        </div>
    </section>
@endsection

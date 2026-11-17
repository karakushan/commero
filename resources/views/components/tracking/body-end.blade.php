{{-- Before </body>: custom code from the site settings (chat widgets etc.). --}}
@php($bodyCode = \Commero\Marketing\Tracking::bodyCode())
@if ($bodyCode)
    {!! $bodyCode !!}
@endif

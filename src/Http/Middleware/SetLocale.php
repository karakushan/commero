<?php

namespace Commero\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', [config('app.locale')]);
        $locale = $request->route('locale');

        if (! is_string($locale) || ! in_array($locale, $supportedLocales, true)) {
            $locale = Arr::first($supportedLocales, static fn (): bool => true, config('app.locale'));
        }

        App::setLocale($locale);

        return $next($request);
    }
}

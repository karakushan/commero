<?php

namespace Commero\Http\Middleware;

use Commero\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        App::setLocale(Locales::resolve(is_string($locale) ? $locale : null));

        return $next($request);
    }
}

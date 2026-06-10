<?php

namespace Commero\Http\Middleware;

use Closure;
use Commero\Models\Currency;
use Commero\Models\SiteSetting;
use Illuminate\Http\Request;

class SetCountryFromUrl
{
    public function handle(Request $request, Closure $next): mixed
    {
        $settings = SiteSetting::query()->first();

        if (! $settings?->isMultiCurrencyEnabled()) {
            return $next($request);
        }

        $countrySource = $settings->getCountrySource();
        $countryCode = $this->resolveCountryCode($request, $countrySource);

        if ($countryCode !== null) {
            app()->instance('current_country', mb_strtoupper($countryCode));
        }

        return $next($request);
    }

    private function resolveCountryCode(Request $request, ?string $source): ?string
    {
        if ($source === 'cookie') {
            return $this->resolveFromCookie($request);
        }

        if ($source === 'url') {
            return $this->resolveFromUrl($request);
        }

        return null;
    }

    private function resolveFromCookie(Request $request): ?string
    {
        return $request->cookie('shophats_country');
    }

    private function resolveFromUrl(Request $request): ?string
    {
        $firstSegment = $request->segment(1);

        if ($firstSegment === null) {
            return $this->resolveFromBaseCurrency();
        }

        if (str_contains($firstSegment, '-')) {
            return $this->parseCountryLocaleSegment($firstSegment);
        }

        return $this->resolveFromLocaleMap($firstSegment);
    }

    private function parseCountryLocaleSegment(string $segment): ?string
    {
        $parts = explode('-', $segment, 2);

        if (count($parts) !== 2) {
            return null;
        }

        $country = $parts[0];
        $locale = $parts[1];

        if ($locale !== '' && $locale !== app()->getLocale()) {
            app()->setLocale($locale);
        }

        return mb_strlen($country) === 2 ? $country : null;
    }

    private function resolveFromLocaleMap(string $locale): ?string
    {
        $countryMap = config('commero.locales.country_map', []);

        if (array_key_exists($locale, $countryMap)) {
            return $countryMap[$locale];
        }

        $currency = Currency::findByCountry(mb_strtoupper($locale));

        return $currency?->country_codes[0] ?? $this->resolveFromBaseCurrency();
    }

    private function resolveFromBaseCurrency(): ?string
    {
        return Currency::getBase()?->country_codes[0] ?? null;
    }
}

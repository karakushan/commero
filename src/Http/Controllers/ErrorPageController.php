<?php

namespace Commero\Http\Controllers;

use Commero\Support\Locales;
use Commero\Support\Seo\LocalizedSeoResolver;
use Illuminate\Support\Facades\App;

class ErrorPageController extends Controller
{
    /**
     * Display the 404 error page
     */
    public function notFound(LocalizedSeoResolver $seoResolver)
    {
        $locale = Locales::resolve(request()->route('locale') ?? app()->getLocale());
        App::setLocale($locale);

        return view('shophats::errors.404', [
            'locale' => $locale,
            'supportedLocales' => Locales::supported(),
            'seo' => $seoResolver->forCurrentRoute(
                request: request(),
                locale: $locale,
                title: __('404 Error - Page Not Found'),
                heading: __('404 Error - Page Not Found'),
                description: __('Page not found. The address may be wrong, or the page no longer exists.'),
            ),
        ]);
    }
}

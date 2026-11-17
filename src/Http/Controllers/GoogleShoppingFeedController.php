<?php

namespace Commero\Http\Controllers;

use Commero\Marketing\GoogleShopping\GoogleShoppingFeed;
use Commero\Support\Locales;
use Illuminate\Http\Response;

class GoogleShoppingFeedController extends Controller
{
    public function __invoke(GoogleShoppingFeed $feed): Response
    {
        abort_unless(GoogleShoppingFeed::enabled(), 404);

        return response($feed->render(Locales::resolve(app()->getLocale())))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex');
    }
}

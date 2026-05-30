<?php

namespace Commero\Http\Controllers;

use Commero\Models\Order;
use Commero\Support\Locales;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class ThankYouController extends Controller
{
    public function show(?string $param1 = null, ?string $param2 = null): View
    {
        // Determine if param1 is a locale or order identifier
        $locale = null;
        $orderReference = null;

        if (in_array($param1, Locales::supported(), true)) {
            // param1 is a locale, param2 is the order identifier
            $locale = $param1;
            $orderReference = $param2;
        } else {
            // param1 is the order identifier (default locale route)
            $orderReference = $param1;
        }

        $resolvedLocale = Locales::resolve($locale);
        App::setLocale($resolvedLocale);

        $order = null;

        if ($orderReference) {
            $orderQuery = Order::query()->with(['items']);

            if (ctype_digit($orderReference)) {
                $order = $orderQuery->find((int) $orderReference);
            } else {
                $order = $orderQuery
                    ->where('number', $orderReference)
                    ->first();
            }
        }

        return view('shophats::pages.thank-you', [
            'locale' => $resolvedLocale,
            'order' => $order,
        ]);
    }
}

<?php

use Commero\Payments\LiqPay\LiqPayGateway;
use Commero\Payments\Monobank\MonobankInstallmentsGateway;
use Commero\Payments\Privat\PrivatPayPartsGateway;
use Commero\Support\ContentBlocks\EmptyContentBlockRegistry;
use Commero\Support\ContentBlocks\NullContentBlockHydrator;

return [
    'theme_view_path' => resource_path('views/shophats'),

    'media' => [
        'original_disk' => 'private',
        'conversion_disk' => 'public',
        'legacy_disk' => 'public',
        'webp' => [
            'enabled' => true,
            'quality' => 82,
        ],
        'sizes' => [
            'tiny' => ['width' => 64, 'height' => null, 'quality' => 72, 'format' => 'webp'],
            'thumb' => ['width' => 320, 'height' => null, 'quality' => 80, 'format' => 'webp'],
            'slider' => ['width' => 345, 'height' => null, 'quality' => 82, 'format' => 'webp'],
            'slider_large' => ['width' => 571, 'height' => null, 'quality' => 85, 'format' => 'webp'],
            'card' => ['width' => 640, 'height' => null, 'quality' => 82, 'format' => 'webp'],
            'detail' => ['width' => 1600, 'height' => null, 'quality' => 85, 'format' => 'webp'],
        ],
        'collections' => [
            'product_gallery' => ['tiny', 'thumb', 'slider', 'slider_large', 'card', 'detail'],
            'category_thumbnail' => ['thumb'],
            'category_icon' => ['thumb'],
            'post_thumbnail' => ['card'],
        ],
        'legacy_compatibility' => [
            'enabled' => true,
            'cleanup' => false,
        ],
        'queue' => [
            'connection' => null,
            'queue' => 'media',
        ],
    ],

    'notifications' => [
        'logo_url' => null,
        'order_received_view' => 'commero::emails.order-notification',
        'order_confirmation_view' => 'commero::emails.order-notification',
        'order_status_changed_view' => 'commero::emails.order-notification',
        'marketing_lead_received_view' => 'commero::emails.marketing-lead-notification',
        'product_review_received_view' => 'commero::emails.product-review-notification',
    ],

    'content_blocks' => [
        'registry' => EmptyContentBlockRegistry::class,
        'hydrator' => NullContentBlockHydrator::class,
    ],

    'tracking' => [
        // Google Tag Manager / Analytics / Meta Pixel and custom code from the site settings.
        'enabled' => env('COMMERO_TRACKING_ENABLED', true),
    ],

    'google_shopping' => [
        // Google Merchant Center feed: /feed/googleshopping/ and /{locale}/feed/googleshopping/.
        'enabled' => true,
        'path' => 'feed/googleshopping',
        // Currency code of g:price (default: the base currency).
        'currency' => null,
        'description' => '',
        // Seconds to cache the generated XML (0 = no cache).
        'cache_ttl' => 0,
    ],

    'sitemap' => [
        'enabled' => true,
        'index_path' => 'sitemap.xml',
        'maps' => [
            'static' => 'sitemap-static.xml',
            'categories' => 'sitemap-categories.xml',
            'products' => 'sitemap-products.xml',
            'blog' => 'sitemap-blog.xml',
            'pages' => 'sitemap-pages.xml',
        ],
        'static_routes' => [
            'home',
            'catalog.index',
            'sale.index',
            'special-offers.index',
            'blog.index',
            'contacts.index',
            'privacy.policy',
        ],
    ],

    'locales' => [
        'supported' => ['uk', 'en', 'ru', 'es', 'pl'],
        'fallback' => 'uk',
        'default' => 'uk',
        'country_map' => [
            'uk' => 'UA',
            'en' => 'GB',
            'ru' => 'RU',
            'es' => 'ES',
            'pl' => 'PL',
        ],
    ],

    'payments' => [
        // Payment method code (payment_methods.code) => online payment gateway.
        'gateways' => [
            'liqpay' => LiqPayGateway::class,
            'privatbank_payparts' => PrivatPayPartsGateway::class,
            'monobank_installments' => MonobankInstallmentsGateway::class,
        ],
        // Order status code applied after a successful online payment (ignored if the status does not exist).
        'paid_order_status' => 'paid',
        // How long the signed payment page link stays valid.
        'link_ttl_days' => 30,
        'liqpay' => [
            'checkout_url' => 'https://www.liqpay.ua/api/3/checkout',
            'currency' => 'UAH',
            // Site locale => LiqPay checkout language (uk, en).
            'languages' => ['uk' => 'uk', 'en' => 'en'],
            'default_language' => 'uk',
        ],
        // PrivatBank "Оплата частинами" (PayParts API v2).
        'privat_payparts' => [
            'api_url' => 'https://payparts2.privatbank.ua/ipp/v2',
            'merchant_type' => 'PP', // PP - Оплата частинами, II - Миттєва розстрочка
            'min_parts' => 2,
            'max_parts' => 24,
            'order_prefix' => '',
            // A created application (token) is reused on the payment page for this long.
            'token_ttl_minutes' => 15,
        ],
        // Monobank "Покупка частинами".
        'monobank_installments' => [
            'api_url' => 'https://u2.monobank.com.ua',
            'sandbox_api_url' => 'https://u2-demo-ext.mono.st4g3.com',
            'min_parts' => 3,
            'max_parts' => 25,
            'point_id' => null,
            // The payment page reloads while the customer confirms the application in the app.
            'refresh_seconds' => 10,
        ],
    ],

    'routing' => [
        'reserved_root_slugs' => [
            'payment',
            'admin',
            'home',
            'login',
            'register',
            'logout',
            'account',
            'lostpassword',
            'reset-password',
        ],
    ],
];

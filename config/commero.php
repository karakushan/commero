<?php

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
            'thumb' => ['width' => 320, 'height' => 320, 'fit' => 'crop', 'quality' => 80, 'format' => 'webp'],
            'card' => ['width' => 640, 'height' => 800, 'fit' => 'crop', 'quality' => 82, 'format' => 'webp'],
            'detail' => ['width' => 1600, 'height' => null, 'fit' => 'contain', 'quality' => 85, 'format' => 'webp'],
        ],
        'collections' => [
            'product_gallery' => ['thumb', 'card', 'detail'],
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

    'routing' => [
        'reserved_root_slugs' => [
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

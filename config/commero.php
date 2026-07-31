<?php

use Commero\Support\ContentBlocks\EmptyContentBlockRegistry;
use Commero\Support\ContentBlocks\NullContentBlockHydrator;

return [
    'theme_view_path' => resource_path('views/shophats'),

    'notifications' => [
        'order_received_view' => 'commero::emails.order-notification',
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

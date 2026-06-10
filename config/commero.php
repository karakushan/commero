<?php

use Commero\Support\ContentBlocks\EmptyContentBlockRegistry;
use Commero\Support\ContentBlocks\NullContentBlockHydrator;

return [
    'theme_view_path' => resource_path('views/shophats'),

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

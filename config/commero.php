<?php

return [
    'theme_view_path' => resource_path('views/shophats'),

    'content_blocks' => [
        'registry' => \Commero\Support\ContentBlocks\EmptyContentBlockRegistry::class,
        'hydrator' => \Commero\Support\ContentBlocks\NullContentBlockHydrator::class,
    ],

    'locales' => [
        'supported' => ['uk', 'en', 'ru'],
        'fallback' => 'uk',
        'default' => 'uk',
    ],
];

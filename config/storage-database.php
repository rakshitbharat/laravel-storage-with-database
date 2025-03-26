<?php

return [
    'default' => env('STORAGE_DISK', 'database'),

    'disks' => [
        'database' => [
            'driver' => 'database',
            'table' => 'storage',
        ],
    ],

    'cache' => [
        'enabled' => env('STORAGE_CACHE_ENABLED', false),
        'store' => env('STORAGE_CACHE_STORE', 'file'),
        'ttl' => env('STORAGE_CACHE_TTL', 3600),
    ],

    'logging' => [
        'enabled' => env('STORAGE_LOGGING_ENABLED', false),
        'channel' => env('STORAGE_LOGGING_CHANNEL', 'stack'),
    ],

    'security' => [
        'validate_content' => env('STORAGE_VALIDATE_CONTENT', true),
        'allowed_mime_types' => env('STORAGE_ALLOWED_MIME_TYPES', []),
        'max_key_length' => env('STORAGE_MAX_KEY_LENGTH', 255),
    ],
];

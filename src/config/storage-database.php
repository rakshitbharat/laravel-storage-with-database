<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Storage Driver
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default database storage driver that should be
    | used by the framework. The "database" driver is the only supported
    | driver currently.
    |
    */
    'default' => env('STORAGE_DATABASE_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Database Storage Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many database storage "disks" as you wish.
    | You can even configure multiple disks of the same driver.
    |
    */
    'disks' => [
        'database' => [
            'driver' => 'database',
            'table' => env('STORAGE_DATABASE_TABLE', 'storage'),
            'connection' => env('STORAGE_DATABASE_CONNECTION', env('DB_CONNECTION', 'mysql')),
            'max_content_size' => env('STORAGE_DATABASE_MAX_SIZE', 10485760), // 10MB
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-run Migrations
    |--------------------------------------------------------------------------
    |
    | This option controls whether package migrations should be auto-run when
    | the package is installed or updated. Set this to false if you would
    | like to run migrations manually.
    |
    */
    'run_migrations' => env('STORAGE_DATABASE_RUN_MIGRATIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Security Options
    |--------------------------------------------------------------------------
    |
    | Configure security-related options. These help protect against unwanted
    | data storage and access.
    |
    */
    'security' => [
        'allowed_mime_types' => explode(',', env('STORAGE_DATABASE_ALLOWED_MIMES', 'text/plain,text/html,application/json')),
        'validate_content' => env('STORAGE_DATABASE_VALIDATE_CONTENT', true),
        'max_key_length' => env('STORAGE_DATABASE_MAX_KEY_LENGTH', 255),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching options for improved performance. Set to false to
    | disable caching.
    |
    */
    'cache' => [
        'enabled' => env('STORAGE_DATABASE_CACHE_ENABLED', false),
        'ttl' => env('STORAGE_DATABASE_CACHE_TTL', 3600), // 1 hour
        'store' => env('STORAGE_DATABASE_CACHE_STORE', 'file'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Options
    |--------------------------------------------------------------------------
    |
    | Configure logging options for the storage operations.
    |
    */
    'logging' => [
        'enabled' => env('STORAGE_DATABASE_LOGGING_ENABLED', false),
        'channel' => env('STORAGE_DATABASE_LOG_CHANNEL', env('LOG_CHANNEL', 'stack')),
    ],
];

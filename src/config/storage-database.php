<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Driver
    |--------------------------------------------------------------------------
    |
    | Here you can specify the default storage driver that will be used
    | by your application. Currently, only 'database' is supported.
    |
    */
    'default' => 'database',

    /*
    |--------------------------------------------------------------------------
    | Database Disk Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the database connection and table name for storing files.
    |
    */
    'disks' => [
        'database' => [
            'driver' => 'database',
            'table' => 'storage',
            'connection' => null, // Uses default connection if null
            'max_content_size' => 10485760, // 10MB default max content size
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for improved performance. Caching can
    | significantly improve read performance for frequently accessed files.
    |
    */
    'cache' => [
        'enabled' => env('STORAGE_DATABASE_CACHE_ENABLED', true),
        'store' => env('STORAGE_DATABASE_CACHE_STORE', 'file'),
        'ttl' => env('STORAGE_DATABASE_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security constraints for file storage. These settings help
    | prevent unwanted content types and oversized files.
    |
    */
    'security' => [
        'validate_content' => env('STORAGE_DATABASE_VALIDATE_CONTENT', true),
        'allowed_mime_types' => [
            'text/plain',
            'text/html',
            'text/css',
            'text/javascript',
            'application/json',
            'application/xml',
        ],
        'max_key_length' => 255,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure monitoring and logging behavior. This helps track storage
    | operations and diagnose issues.
    |
    */
    'logging' => [
        'enabled' => env('STORAGE_DATABASE_LOGGING_ENABLED', false),
        'channel' => env('STORAGE_DATABASE_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Migration Settings
    |--------------------------------------------------------------------------
    |
    | Control whether migrations should be run automatically when the
    | package is installed or upgraded.
    |
    */
    'run_migrations' => env('STORAGE_DATABASE_RUN_MIGRATIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Batch Operation Settings
    |--------------------------------------------------------------------------
    |
    | Configure behavior for batch operations like putMany and deleteMany.
    |
    */
    'batch' => [
        'chunk_size' => env('STORAGE_DATABASE_BATCH_CHUNK_SIZE', 100),
        'transaction_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Directory Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for directory listings to improve
    | performance when working with large directory structures.
    |
    */
    'directory_cache' => [
        'enabled' => env('STORAGE_DATABASE_DIRECTORY_CACHE_ENABLED', true),
        'ttl' => env('STORAGE_DATABASE_DIRECTORY_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure performance monitoring settings. These help track and
    | optimize storage operations.
    |
    */
    'monitoring' => [
        'track_size' => true,
        'track_mime_type' => true,
        'track_checksum' => true,
        'stats_retention_days' => 30,
    ],
];

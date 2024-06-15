<?php

return [
    'default' => env('STORAGE_DATABASE_DRIVER', 'database'),

    'disks' => [
        'database' => [
            'driver' => 'database',
            'table' => 'storage',
            'connection' => 'mysql',
        ],
    ],
];

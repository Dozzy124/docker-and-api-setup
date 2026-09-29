<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain / Path
    |--------------------------------------------------------------------------
    | Only relevant if you expose the Horizon dashboard behind its own
    | subdomain. For API-only local dev it's reached at /horizon.
    */
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Redis connection used by Horizon
    |--------------------------------------------------------------------------
    | Must match a connection defined in config/database.php ("redis").
    */
    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'medai'), '_') . '_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    | Restrict dashboard access — see app/Providers/HorizonServiceProvider.php
    | (gate 'viewHorizon') for the actual authorization rule.
    */
    'middleware' => ['web', 'auth:sanctum'],

    'waits' => [
        'redis:default' => 60,
        'redis:documents' => 60,
        'redis:notifications' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [
        // App\Jobs\SilencedJob::class,
    ],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    | "documents" is the dedicated queue for ProcessDocumentJob (AI pipeline,
    | §5 of the ТЗ). Keeping it separate from "default" means a slow AI call
    | never blocks lightweight jobs (e.g. notification dispatch).
    */
    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
                'queue' => ['default', 'documents', 'notifications'],
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],

        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'queue' => ['default', 'notifications'],
            ],
            'supervisor-2' => [
                'connection' => 'redis',
                'queue' => ['documents'],
                'balance' => 'auto',
                'maxProcesses' => 5,
                'tries' => 3,
                'timeout' => 300, // AI calls can be slow
            ],
        ],
    ],
];

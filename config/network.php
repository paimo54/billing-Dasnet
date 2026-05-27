<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Network Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for FreeRADIUS and Mikrotik integration
    |
    */

    'primary_method' => env('NETWORK_PRIMARY_METHOD', 'radius'), // radius or mikrotik

    /*
    |--------------------------------------------------------------------------
    | FreeRADIUS Configuration
    |--------------------------------------------------------------------------
    */
    'radius' => [
        'enabled' => env('RADIUS_ENABLED', true),

        // Database connection (uses default Laravel DB connection)
        'connection' => env('RADIUS_DB_CONNECTION', 'mysql'),

        // CoA (Change of Authorization) settings
        'coa' => [
            'enabled' => env('RADIUS_COA_ENABLED', false),
            'host' => env('RADIUS_COA_HOST', '127.0.0.1'),
            'port' => env('RADIUS_COA_PORT', 3799),
            'secret' => env('RADIUS_COA_SECRET', 'testing123'),
        ],

        // Default bandwidth limits
        'default_bandwidth' => [
            'download' => env('RADIUS_DEFAULT_DOWNLOAD', '10M'),
            'upload' => env('RADIUS_DEFAULT_UPLOAD', '10M'),
        ],

        // Session timeout (in seconds)
        'session_timeout' => env('RADIUS_SESSION_TIMEOUT', 0), // 0 = unlimited

        // Idle timeout (in seconds)
        'idle_timeout' => env('RADIUS_IDLE_TIMEOUT', 0), // 0 = unlimited

        // Concurrent session limit
        'concurrent_sessions' => env('RADIUS_CONCURRENT_SESSIONS', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mikrotik Configuration
    |--------------------------------------------------------------------------
    */
    'mikrotik' => [
        'enabled' => env('MIKROTIK_ENABLED', true),

        // Primary router (for single router setup)
        'host' => env('MIKROTIK_HOST', '192.168.1.1'),
        'username' => env('MIKROTIK_USERNAME', 'admin'),
        'password' => env('MIKROTIK_PASSWORD', ''),
        'port' => env('MIKROTIK_PORT', 8728),

        // Multiple routers (for multi-router setup)
        'routers' => [
            // Example:
            // 'router1' => [
            //     'host' => '192.168.1.1',
            //     'username' => 'admin',
            //     'password' => 'password',
            //     'port' => 8728,
            // ],
        ],

        // Connection timeout (seconds)
        'timeout' => env('MIKROTIK_TIMEOUT', 5),

        // Create Mikrotik user in addition to RADIUS
        'create_mikrotik_user' => env('MIKROTIK_CREATE_USER', false),

        // Default PPPoE profile
        'default_profile' => env('MIKROTIK_DEFAULT_PROFILE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Settings
    |--------------------------------------------------------------------------
    */
    'settings' => [
        // Auto-unsuspend after payment
        'auto_unsuspend_after_payment' => env('NETWORK_AUTO_UNSUSPEND', true),

        // Grace period before suspend (days)
        'suspend_grace_period' => env('NETWORK_SUSPEND_GRACE_PERIOD', 7),

        // Disconnect active sessions on suspend
        'disconnect_on_suspend' => env('NETWORK_DISCONNECT_ON_SUSPEND', true),

        // Username format
        'username_format' => env('NETWORK_USERNAME_FORMAT', 'customer_{id}'), // customer_{id}, {phone}, {email}

        // Password generation
        'auto_generate_password' => env('NETWORK_AUTO_GENERATE_PASSWORD', true),
        'password_length' => env('NETWORK_PASSWORD_LENGTH', 8),
    ],

];

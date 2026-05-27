<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mikrotik Configuration
    |--------------------------------------------------------------------------
    */

    'host' => env('MIKROTIK_HOST', '192.168.1.1'),
    'username' => env('MIKROTIK_USERNAME', 'admin'),
    'password' => env('MIKROTIK_PASSWORD', ''),
    'port' => env('MIKROTIK_PORT', 8728),
    'timeout' => env('MIKROTIK_TIMEOUT', 5),

];

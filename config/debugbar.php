<?php
return [
    'enabled' => env('DEBUGBAR_ENABLED', false),
    'except' => [
        'telescope*',
        'horizon*',
    ],
    'storage' => [
        'enabled' => true,
        'driver' => 'file',
        'path' => storage_path('debugbar'),
        'connection' => null,
        'provider' => '',
        'hostname' => '127.0.0.1',
        'port' => 2304,
    ],
    'include_vendors' => true,
    'capture_ajax' => true,
    'add_ajax_timing' => false,
    'collectors' => [
        'phpinfo' => false,  // Disable for security
        'messages' => true,
        'time' => true,
        'memory' => true,
        'exceptions' => true,
        'log' => true,
        'db' => true,
        'views' => true,
        'route' => true,
        'auth' => false,  // Disable in production
        'gate' => true,
        'session' => false,  // Disable in production
        'symfony_request' => true,
        'mail' => true,
        'laravel' => true,
        'events' => false,  // Can be heavy
        'default_request' => false,
        'logs' => false,
        'files' => false,
        'config' => false,  // Disable for security
        'cache' => false,
    ],
];
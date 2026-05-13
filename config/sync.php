<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sync API Tokens
    |--------------------------------------------------------------------------
    |
    | Tokens válidos para autenticar requests de sincronización desde Python.
    | Puedes generar tokens únicos para cada instancia de Python.
    |
    */

    'api_tokens' => [
        // env('SYNC_API_TOKEN'), // Usar token desde .env
        // 'production-python-instance-1' => 'your-token-here',
        // 'development-python-instance' => 'dev-token-here',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    |
    | Tamaño predeterminado del lote para sincronización.
    |
    */

    'batch_size' => env('SYNC_BATCH_SIZE', 1000),

    /*
    |--------------------------------------------------------------------------
    | Max Records Per Request
    |--------------------------------------------------------------------------
    |
    | Máximo número de registros permitidos por request.
    |
    */

    'max_records_per_request' => [
        'products' => 5000,
        'customers' => 5000,
        'quotes' => 1000,
        'sellers' => 1000,
        'categories' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Límites de tasa para endpoints de sincronización.
    |
    */

    'rate_limit' => [
        'max_attempts' => env('SYNC_RATE_LIMIT_MAX', 60), // 60 requests
        'decay_minutes' => env('SYNC_RATE_LIMIT_DECAY', 1), // por minuto
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de reintentos para conexiones a MySQL remoto.
    |
    */

    'retry' => [
        'max_attempts' => 3,
        'backoff_seconds' => 30,
    ],

];

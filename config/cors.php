<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Orígenes permitidos acotados al frontend. Se leen de CORS_ALLOWED_ORIGINS
    | (lista separada por comas en el .env); por defecto, los puertos de desarrollo.
    | No se usan credenciales de cookie porque la API autentica con token Bearer.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:3000,http://localhost:3001'
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];

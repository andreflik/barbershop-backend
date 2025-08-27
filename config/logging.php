<?php

use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Em produção, prefira 'daily' com retenção baixa e nível 'warning' (ou 'info').
    | Incluímos um processor para sanitizar dados sensíveis (se a classe existir).
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false,

            // Processors (rodando em todos os logs do stack)
            'processors' => array_values(array_filter([
                class_exists(\App\Logging\SanitizeSensitiveData::class)
                    ? \App\Logging\SanitizeSensitiveData::class
                    : null,
                PsrLogMessageProcessor::class,
            ])),
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'replace_placeholders' => true,
            'processors' => array_values(array_filter([
                class_exists(\App\Logging\SanitizeSensitiveData::class)
                    ? \App\Logging\SanitizeSensitiveData::class
                    : null,
                PsrLogMessageProcessor::class,
            ])),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'days' => env('LOG_DAYS', 7),
            'replace_placeholders' => true,
            'processors' => array_values(array_filter([
                class_exists(\App\Logging\SanitizeSensitiveData::class)
                    ? \App\Logging\SanitizeSensitiveData::class
                    : null,
                PsrLogMessageProcessor::class,
            ])),
        ],

        // Canais auxiliares usuais
        'stderr' => [
            'driver' => 'monolog',
            'handler' => Monolog\Handler\StreamHandler::class,
            'with' => [
                'stream' => 'php://stderr',
            ],
            'level' => env('LOG_LEVEL', 'warning'),
            'processors' => array_values(array_filter([
                class_exists(\App\Logging\SanitizeSensitiveData::class)
                    ? \App\Logging\SanitizeSensitiveData::class
                    : null,
                PsrLogMessageProcessor::class,
            ])),
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'warning'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'warning'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => Monolog\Handler\NullHandler::class,
        ],
    ],

];

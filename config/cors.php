<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Restrinja às rotas da API. Se usar Sanctum/cookies para SPA, poderemos
    | incluir 'sanctum/csrf-cookie' aqui depois.
    |
    */

    'paths' => ['api/*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    */

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Defina o domínio do seu frontend (Netlify, p.ex.).
    | Em dev, use http://localhost:8080 ou o que você usa.
    |
    */

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:8080'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Evite curingas amplos. Se precisar permitir *-netlify.app, use um pattern:
    | 'allowed_origins_patterns' => ['~^https://.+\.netlify\.app$~'],
    |
    */

    'allowed_origins_patterns' => [
        // vazio por padrão
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    */

    'allowed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Evite expor Authorization/Set-Cookie no navegador.
    |
    */

    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    */

    'max_age' => 86400,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Deixe "false" se usar Bearer token em header (recomendado).
    | Se migrar para cookie HttpOnly/Sanctum, mude para true e ajuste:
    | - CORS do front com credentials: 'include'
    | - SameSite=None no cookie
    |
    */

    'supports_credentials' => false,

];

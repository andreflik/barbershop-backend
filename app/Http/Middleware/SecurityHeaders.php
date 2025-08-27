<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // Remove qualquer header que possa revelar stack/infra
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // Endureça respostas
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload'); // HTTPS only
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        // Ajuste a CSP conforme sua necessidade (comentar se quebrar algo do front):
        // $response->headers->set('Content-Security-Policy', "default-src 'self'; img-src 'self' data: https:; script-src 'self'; style-src 'self' 'unsafe-inline' https:; connect-src 'self' https:;");

        // Limite APIs do navegador
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}

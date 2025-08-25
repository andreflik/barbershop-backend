<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $origin = $request->headers->get('Origin');
        $allowed = [
            'https://barbershop-marquinhos.netlify.app',
            'http://localhost:8080',
        ];

        if ($origin && in_array($origin, $allowed, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            $request->header('Access-Control-Request-Headers', 'Authorization, Content-Type, X-Requested-With')
        );
        $response->headers->set('Access-Control-Max-Age', '86400');

        if ($request->getMethod() === 'OPTIONS') {
            $empty = response('', 204);
            $empty->headers->add($response->headers->all());
            return $empty;
        }

        return $response;
    }
}

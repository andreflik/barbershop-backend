<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Http\Middleware\HandleCors;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\EnsureUserIsAdmin; // <- importa o middleware admin

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: base_path('routes/web.php'),
        api: base_path('routes/api.php'),
        commands: base_path('routes/console.php'),
        health: '/up',
    )
     ->withProviders([\App\Providers\RouteServiceProvider::class])

    ->withMiddleware(function (Middleware $middleware) {
        // Confiar nos proxies (Koyeb/NGINX, etc.)
        $middleware->trustProxies(at: '*');

        // CORS primeiro da pilha
        $middleware->prepend(HandleCors::class);

        // Cabeçalhos de segurança (sem dados sensíveis)
        $middleware->append(SecurityHeaders::class);

        // 🔐 Aliases de middleware (AQUI ENTRA O ADMIN)
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            // adicione outros aliases se tiver
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, $request) {
            // ⚠️ Recomendo não delegar ao handler padrão nem em dev,
            //     para sempre retornar JSON limpo na API.
            // if (config('app.debug')) { return null; }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Dados inválidos.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => 'Não autenticado.'], 401);
            }

            if ($e instanceof AuthorizationException) {
                // Evita "This action is unauthorized." com stack gigante
                return response()->json(['message' => 'Ação não autorizada.'], 403);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json(['message' => 'Operação não permitida.'], $e->getStatusCode());
            }

            // Log interno enxuto (sem dados sensíveis)
            try {
                \Log::error('Erro interno', [
                    'type'   => get_class($e),
                    'url'    => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
            } catch (\Throwable $ignored) {}

            return response()->json(['message' => 'Erro interno.'], 500);
        });
    })
    ->create();

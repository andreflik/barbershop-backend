<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Garante que o usuário autenticado possui papel de administrador.
     *
     * - Requer que o auth:sanctum já tenha rodado antes.
     * - Retorna JSON com 401 se não autenticado e 403 se não for admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Se ainda não autenticado, retorne 401 (fallback de segurança)
        if (!$user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        /**
         * Papéis permitidos. Você pode sobrescrever via config:
         *  Em config/auth.php, adicione:
         *    'admin_roles' => ['adm'],
         * Caso não exista, usamos o default abaixo.
         */
        $adminRoles = config('auth.admin_roles', ['adm']);

        if (!in_array($user->role ?? null, $adminRoles, true)) {
            if (config('app.debug')) {
                Log::warning('EnsureUserIsAdmin: acesso negado', [
                    'user_id' => $user->id ?? null,
                    'role'    => $user->role ?? null,
                    'path'    => $request->path(),
                    'method'  => $request->method(),
                ]);
            }
            return response()->json(['message' => 'Ação não autorizada.'], 403);
        }

        return $next($request);
    }
}

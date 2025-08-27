<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // normaliza email
        $credentials = [
            'email'    => strtolower($validated['email']),
            'password' => $validated['password'],
        ];

        if (!Auth::attempt($credentials)) {
            // mensagem genérica (sem revelar qual campo falhou)
            return response()->json(['message' => 'Invalid login credentials.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // gera token Sanctum (expiração via SANCTUM_EXPIRATION)
        $token = $user->createToken('AppToken')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'role'         => $user->role,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        // validação correta (seu código anterior tinha regras fora do lugar)
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email:rfc,dns|max:255|unique:' . (new User())->getTable() . ',email',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role'     => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Cadastro realizado com sucesso!',
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuário não autenticado ou token inválido'], 401);
        }

        $user->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logout realizado.']);
    }

    /**
     * (Opcional) Logout de todos dispositivos
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuário não autenticado ou token inválido'], 401);
        }

        $user->tokens()->delete();

        return response()->json(['message' => 'Todos os tokens foram revogados.']);
    }
}

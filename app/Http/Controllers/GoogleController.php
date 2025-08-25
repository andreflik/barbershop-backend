<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->stateless()
            ->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser || !$googleUser->email) {
                return response()->json(['error' => 'Erro ao autenticar com o Google.'], 401);
            }

            $user = User::updateOrCreate(
                ['email' => $googleUser->email],
                [
                    'name'  => $googleUser->name ?: $googleUser->nickname,
                    'password' => bcrypt('google-login'),
                    'role'  => $googleUser->email === 'andreflik@gmail.com' ? 'adm' : 'user',
                ]
            );

            Auth::login($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            $frontend = rtrim(env('FRONTEND_URL', 'http://localhost:8080'), '/');

            return redirect()->away(
                $frontend . '/dashboard?token=' . $token .
                '&user=' . urlencode($user->name) .
                '&role=' . $user->role
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Falha ao autenticar com Google: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUsername(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!Auth::check()) {
            \Log::info('Usuário não autenticado.');
            return response()->json(['error' => 'Usuário não identificado'], 401);
        }

        $user = $request->user();
        \Log::info('Nome do usuário autenticado:', ['name' => $user->name]);
        return response()->json(['user' => $user->name], 200);
    }
}

<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GoogleController extends Controller
{
    public function redirectToGoogle(): JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
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
                return response()->json(['error' => 'Falha na autenticação.'], 401);
            }

            // e-mail normalizado (minúsculo)
            $email = strtolower($googleUser->email);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'     => $googleUser->name ?: $googleUser->nickname,

                    // senha randômica para evitar login por senha acidental
                    'password' => Hash::make(Str::random(40)),

                    // admins permitidos
                    'role'     => in_array($email, [
                        'marquinholijs@gmail.com',
                        'andreflik@gmail.com'
                    ]) ? 'adm' : 'user',
                ]
            );

            Auth::login($user);

            $tokenResult = $user->createToken('auth_token');

            $token = $tokenResult->plainTextToken;

            $tokenResult->accessToken->expires_at = now()->addHour();
            $tokenResult->accessToken->save();

            $frontend = rtrim(env('FRONTEND_URL', 'http://localhost:8080'), '/');

            return redirect()->away(
                $frontend . '/dashboard#token=' . $token .
                    '&user=' . urlencode($user->name) .
                    '&role=' . $user->role
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Falha ao autenticar com Google.'], 500);
        }
    }

    public function getUsername(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Usuário não identificado'], 401);
        }

        $user = $request->user();
        return response()->json(['user' => $user->name], 200);
    }
}

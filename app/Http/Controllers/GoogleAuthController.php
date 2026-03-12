<?php

namespace App\Http\Controllers;

use App\Models\AuthProvider;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Debug endpoint to check Google OAuth configuration.
     */
    public function debug(): JsonResponse
    {
        return response()->json([
            'config' => [
                'client_id' => config('services.google.client_id') ? 'Configurado ✓' : 'NO configurado ✗',
                'client_secret' => config('services.google.client_secret') ? 'Configurado ✓' : 'NO configurado ✗',
                'redirect_uri' => config('services.google.redirect'),
            ],
            'request_params' => request()->all(),
            'full_url' => request()->fullUrl(),
        ], 200);
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback(): JsonResponse
    {
        try {
            // Check if user denied access or there's an error
            if (request()->has('error')) {
                return response()->json([
                    'message' => 'Acceso denegado por el usuario.',
                    'error' => request()->get('error'),
                ], 400);
            }

            // Check if code parameter is present
            if (!request()->has('code')) {
                return response()->json([
                    'message' => 'Código de autorización no recibido.',
                    'error' => 'Parámetro code faltante en la respuesta de Google.',
                ], 400);
            }

            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if user already exists by provider
            $authProvider = AuthProvider::where('provider', 'google')
                ->where('provider_user_id', $googleUser->getId())
                ->first();

            if ($authProvider) {
                // User exists with this Google account
                $user = $authProvider->user;
            } else {
                // Check if user exists by email
                $user = User::where('email', $googleUser->getEmail())->first();

                if (!$user) {
                    // Create new user
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'password' => Hash::make(Str::random(24)),
                        'email_verified_at' => now(), // Auto-verify Google users
                    ]);

                    event(new Registered($user));
                } else {
                    // User exists - auto-verify email if not verified
                    if (is_null($user->email_verified_at)) {
                        $user->email_verified_at = now();
                        $user->save();
                    }
                }

                // Link Google account to user
                AuthProvider::create([
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'provider_user_id' => $googleUser->getId(),
                ]);
            }

            // Generate Sanctum token
            $token = $user->createToken('google-auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Login con Google exitoso.',
                'user' => $user,
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al autenticar con Google.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

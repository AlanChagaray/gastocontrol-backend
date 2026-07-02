<?php

namespace App\Http\Controllers;

use App\Models\AuthProvider;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirect()
    {
        // Generar un state de un solo uso (protección CSRF del flujo OAuth).
        // El backend lo gestiona por completo: el frontend solo navega a esta ruta.
        $state = Str::random(40);
        Cache::put("google_oauth_state:{$state}", true, now()->addMinutes(10));

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
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

            // Validar el state de OAuth (protección CSRF) — token de un solo uso.
            $state = request()->get('state');
            if (!$state || !Cache::pull("google_oauth_state:{$state}")) {
                return response()->json([
                    'message' => 'Estado de OAuth inválido o expirado.',
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

                    // Crear las categorías por defecto para el usuario nuevo
                    Category::createDefaultsForUser($user->id);

                    event(new Registered($user));
                } else {
                    // Solo auto-vincular/verificar si Google confirma el email como verificado.
                    // Evita el takeover de una cuenta password cuyo email nunca se verificó.
                    $emailVerifiedByGoogle = $googleUser->user['email_verified'] ?? false;
                    if (!$emailVerifiedByGoogle) {
                        return response()->json([
                            'message' => 'No se puede vincular la cuenta: el email de Google no está verificado.',
                        ], 422);
                    }

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
            Log::error('Error en autenticación con Google', ['exception' => $e]);
            return response()->json([
                'message' => 'Error al autenticar con Google.',
            ], 500);
        }
    }
}

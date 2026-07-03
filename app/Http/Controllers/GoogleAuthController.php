<?php

namespace App\Http\Controllers;

use App\Models\AuthProvider;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
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
    public function callback(): RedirectResponse
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        try {
            // Check if user denied access or there's an error
            if (request()->has('error')) {
                return redirect()->away("{$frontendUrl}/auth/callback?error=access_denied");
            }

            // Check if code parameter is present
            if (!request()->has('code')) {
                return redirect()->away("{$frontendUrl}/auth/callback?error=missing_code");
            }

            // Validar el state de OAuth (protección CSRF) — token de un solo uso.
            $state = request()->get('state');
            if (!$state || !Cache::pull("google_oauth_state:{$state}")) {
                return redirect()->away("{$frontendUrl}/auth/callback?error=invalid_state");
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
                        return redirect()->away("{$frontendUrl}/auth/callback?error=email_not_verified");
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

            return redirect()->away("{$frontendUrl}/auth/callback#token=" . urlencode($token));
        } catch (\Exception $e) {
            Log::error('Error en autenticación con Google', ['exception' => $e]);
            return redirect()->away("{$frontendUrl}/auth/callback?error=google_auth_failed");
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Trigger email verification notification
        event(new Registered($user));

        // Generate Sanctum token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado exitosamente. Por favor verifica tu email.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login with email and password.
     */
public function login(LoginRequest $request): JsonResponse
{
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Credenciales inválidas.',
        ], 401);
    }

    // Opcional: eliminar tokens anteriores (solo 1 sesión activa)
    $user->tokens()->delete();

    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'message' => 'Login exitoso.',
        'user' => $user,
        'token' => $token,
    ]);
}
    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            $request->user(),
        ], 200);
    }

    /**
     * Logout (invalidate current token).
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
        ], 200);
    }
}

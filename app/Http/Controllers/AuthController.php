<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Category;
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
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Crear las categorías por defecto para el usuario nuevo
        Category::createDefaultsForUser($user->id);

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
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        $user = Auth::user();

        // Generate Sanctum token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso.',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * Send password reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        // Intentamos enviar el enlace, pero respondemos SIEMPRE lo mismo,
        // exista o no la cuenta, para no permitir enumeración de usuarios.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            Log::info('Solicitud de reseteo de contraseña sin envío', ['status' => $status]);
        }

        return response()->json([
            'message' => 'Si el email está registrado, te enviamos un enlace de recuperación.',
        ], 200);
    }

    /**
     * Reset password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Contraseña restablecida exitosamente.',
            ], 200);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}

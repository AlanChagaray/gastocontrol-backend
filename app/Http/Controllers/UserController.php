<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Verify user email.
     */

    public function index(){
            $users = User::all();
            return response()->json($users, 200);
    }

    public function show($id){
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        return response()->json($user, 200);
    }


    public function verifyEmail(Request $request, $id, $hash): JsonResponse
    {
        // 1. Validar firma
        if (! $request->hasValidSignature()) {
            return response()->json([
                'message' => 'Link inválido o expirado.'
            ], 403);
        }

        // 2. Buscar usuario
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        // 3. Validar hash
        if (! hash_equals($hash, sha1($user->email))) {
            return response()->json([
                'message' => 'Hash inválido.'
            ], 403);
        }

        // 4. Verificar si ya está verificado
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email ya verificado.'
            ]);
        }

        // 5. Marcar como verificado
        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Email verificado correctamente.'
        ]);
    }
    /**
     * Resend email verification notification.
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email ya verificado.',
            ], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Enlace de verificación enviado.',
        ], 200);
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


    public function income(Request $request): JsonResponse
    {
        $dateParam = $request->query('date', now()->format('Y-m'));
        $year = substr($dateParam, 0, 4);
        $month = substr($dateParam, 5, 2);

        if($amount = $request->input('amount')) {
            $user = $request->user();

            // Buscar o crear income para este mes
            $income = $user->incomes()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->first();

            if ($income) {
                $income->update(['amount' => $amount]);
            } else {
                $income = $user->incomes()->create([
                    'amount' => $amount,
                    'date' => $year . '-' . $month . '-01',
                    'description' => 'Ingreso mensual',
                ]);
            }

            return response()->json([
                'message' => 'Ingreso actualizado.',
                'amount' => $income->amount,
            ], 200);
        }

        $user = $request->user();
        $totalIncome = $user->incomes()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');

        return response()->json([
            'amount' => $totalIncome,
        ], 200);
    }
}

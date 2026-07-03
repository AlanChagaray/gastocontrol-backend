<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateIncomeRequest;
use App\Models\MonthlyIncome;
use Illuminate\Http\Request;

class UserIncomeController extends Controller
{
    /**
     * Muestra el ingreso mensual del usuario para el mes indicado.
     * Recibe la fecha en el query param `date` (formato Y-m o Y-m-d).
     */
    public function show(Request $request)
    {
        [$year, $month] = $this->resolveYearMonth($request);

        $income = MonthlyIncome::where('user_id', $request->user()->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'amount' => $income->amount ?? 0,
        ], 200);
    }

    /**
     * Crea o actualiza (upsert) el ingreso mensual del usuario para el mes indicado.
     */
    public function update(UpdateIncomeRequest $request)
    {
        [$year, $month] = $this->resolveYearMonth($request);

        $income = MonthlyIncome::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'amount' => $request->validated()['amount'],
            ]
        );

        return response()->json([
            'year' => $year,
            'month' => $month,
            'amount' => $income->amount,
        ], 200);
    }

    /**
     * Extrae año y mes del query param `date` (default: mes actual),
     * siguiendo el mismo patrón que ExpensesController::byMonth.
     */
    private function resolveYearMonth(Request $request): array
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);
        $date = $validated['date'] ?? now()->format('Y-m-d');

        $dateObj = \Carbon\Carbon::parse($date);

        return [$dateObj->year, $dateObj->month];
    }
}

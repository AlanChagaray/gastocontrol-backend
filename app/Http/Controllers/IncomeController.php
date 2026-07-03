<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $incomes = Income::where('user_id', $request->user()->id)
            ->select('id', 'name', 'amount', 'income_date')
            ->get();
        return response()->json($incomes, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIncomeRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;
        $income = Income::create($validated);
        return response()->json($income, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $income = Income::where('user_id', $request->user()->id)->find($id);
        if (!$income) {
            return response()->json(['message' => 'Ingreso no encontrado'], 404);
        }
        return response()->json($income, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIncomeRequest $request, $id)
    {
        $income = Income::where('user_id', $request->user()->id)->find($id);
        if (!$income) {
            return response()->json(['message' => 'Ingreso no encontrado'], 404);
        }
        $income->update($request->validated());
        return response()->json($income, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $income = Income::where('user_id', $request->user()->id)->find($id);
        if (!$income) {
            return response()->json(['message' => 'Ingreso no encontrado'], 404);
        }
        $income->delete();
        return response()->json(['message' => 'Ingreso eliminado exitosamente'], 200);
    }

    public function byMonth(Request $request)
    {
        // Recibe fecha en formato date (ej: 2026-03-15 o 2026-03)
        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);
        $date = $validated['date'] ?? now()->format('Y-m-d');

        $dateObj = \Carbon\Carbon::parse($date);
        $month = $dateObj->month;
        $year = $dateObj->year;

        $incomes = Income::where('user_id', $request->user()->id)
            ->whereMonth('income_date', $month)
            ->whereYear('income_date', $year)
            ->select('id', 'name', 'amount', 'income_date')
            ->get();

        return response()->json($incomes, 200);
    }
}

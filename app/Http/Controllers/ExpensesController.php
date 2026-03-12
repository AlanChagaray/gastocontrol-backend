<?php

namespace App\Http\Controllers;

use App\Models\Expenses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpensesRequest;
use App\Http\Requests\UpdateExpensesRequest;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $expenses = Expenses::where('user_id', $request->user()->id)
            ->select('id', 'category_id', 'amount', 'merchant', 'expense_date', 'notes')
            ->with('category:id,name,icon,color')
            ->get();
        return response()->json($expenses, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpensesRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;
        $expense = Expenses::create($validated);
        return response()->json($expense, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $expense = Expenses::where('user_id', $request->user()->id)->with('category:id,name,icon,color')->find($id);
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }
        return response()->json($expense, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpensesRequest $request, $id)
    {
        $expense = Expenses::where('user_id', $request->user()->id)->find($id);
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }
        $validated = $request->validated();
        $expense->update($validated);
        return response()->json($expense, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $expense = Expenses::where('user_id', $request->user()->id)->find($id);
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }
        $expense->delete();
        return response()->json(['message' => 'Gasto eliminado exitosamente'], 200);
    }

    public function byMonth(Request $request)
    {
        // Recibe fecha en formato date (ej: 2026-03-15)
        $date = $request->query('date', now()->format('Y-m-d'));
        
        // Extrae mes y año de la fecha
        $dateObj = \Carbon\Carbon::parse($date);
        $month = $dateObj->month;
        $year = $dateObj->year;
        
        $expenses = Expenses::where('user_id', $request->user()->id)
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->select('id', 'category_id', 'amount', 'merchant', 'expense_date', 'notes')
            ->with('category:id,name,icon,color')
            ->get();

        return response()->json($expenses, 200);
    }

}

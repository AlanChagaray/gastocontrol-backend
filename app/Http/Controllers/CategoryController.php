<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('user_id', $request->user()->id)->get();
        return response()->json($categories, 200);
    }

    public function store(StoreCategoryRequest $request)
    {

        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;
        $category = Category::create($validated);
        return response()->json($category, 201);
    }

    public function show(Request $request, $id)
    {
        $category = Category::where('user_id', $request->user()->id)->find($id);
        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }
        return response()->json($category, 200);
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        $category = Category::where('user_id', $request->user()->id)->find($id);
        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        $validated = $request->validated();
        $category->update($validated);
        return response()->json($category, 200);
    }

    public function destroy(Request $request, $id)
    {
        $category = Category::where('user_id', $request->user()->id)->find($id);
        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }
        $category->delete();
        return response()->json(['message' => 'Categoría eliminada'], 200);
    }
}

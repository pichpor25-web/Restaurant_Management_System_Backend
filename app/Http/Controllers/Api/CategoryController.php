<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'description', 'is_active']);

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created',
            'data' => $category,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::find($id);

        if ($category === null) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = Category::find($id);

        if ($category === null) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated',
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::find($id);

        if ($category === null) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted']);
    }
}

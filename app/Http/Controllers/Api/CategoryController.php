<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    private string $table = 'categories';

    // GET /api/categories
    public function index()
    {
        $categories = DB::table($this->table)
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($categories);
    }

    // POST /api/categories
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $data['is_active'] ?? true;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table($this->table)->insertGetId($data);

        $category = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return response()->json($category, 201);
    }

    // GET /api/categories/{id}
    public function show(string $id)
    {
        $category = DB::table($this->table)
            ->where('id', $id)
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
    }

    // PUT /api/categories/{id}
    public function update(Request $request, string $id)
    {
        $exists = DB::table($this->table)
            ->where('id', $id)
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'description' => 'sometimes|nullable|string',
            'image' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['updated_at'] = now();

        DB::table($this->table)
            ->where('id', $id)
            ->update($data);

        $category = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return response()->json($category);
    }

    // DELETE /api/categories/{id}
    public function destroy(string $id)
    {
        $deleted = DB::table($this->table)
            ->where('id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
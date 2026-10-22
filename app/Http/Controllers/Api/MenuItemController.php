<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuItemController extends Controller
{
    private string $table = 'menu_items';

    // GET /api/menu-items
    public function index()
    {
        $items = DB::table($this->table)
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($items);
    }

    // POST /api/menu-items
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'is_available' => 'sometimes|boolean',
        ]);

        $data['is_available'] = $data['is_available'] ?? true;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table($this->table)->insertGetId($data);

        $item = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return response()->json($item, 201);
    }

    // GET /api/menu-items/{id}
    public function show(string $id)
    {
        $item = DB::table($this->table)
            ->where('id', $id)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        return response()->json($item);
    }

    // PUT /api/menu-items/{id}
    public function update(Request $request, string $id)
    {
        $exists = DB::table($this->table)
            ->where('id', $id)
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $data = $request->validate([
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'name' => 'sometimes|required|string|max:150',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'image' => 'sometimes|nullable|string|max:255',
            'is_available' => 'sometimes|boolean',
        ]);

        $data['updated_at'] = now();

        DB::table($this->table)
            ->where('id', $id)
            ->update($data);

        $item = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return response()->json($item);
    }

    // DELETE /api/menu-items/{id}
    public function destroy(string $id)
    {
        $deleted = DB::table($this->table)
            ->where('id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        return response()->json(['message' => 'Menu item deleted successfully']);
    }
}

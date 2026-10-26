<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    private string $table = 'menu_items';

    // Image rules: a real uploaded file (not a URL string any more)
    private const IMAGE_RULE = 'image|mimes:jpg,jpeg,png,webp|max:2048'; // 2 MB

    // GET /api/menu-items?per_page=100&category_id=1&available=1
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $items = DB::table($this->table)
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->boolean('available'), fn ($q) => $q->where('is_available', true))
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($items);
    }

    // POST /api/menu-items   (multipart/form-data)
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|' . self::IMAGE_RULE,
            'is_available' => 'sometimes|boolean',
        ]);

        // Save the file and keep only its path (e.g. "menu-items/abc123.jpg")
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('menu-items', 'public');
        }

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
    // The frontend sends POST + _method=PUT (needed for multipart), Laravel routes it as PUT.
    public function update(Request $request, string $id)
    {
        $current = DB::table($this->table)
            ->where('id', $id)
            ->first();

        if (!$current) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        $data = $request->validate([
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'name' => 'sometimes|required|string|max:150',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'image' => 'sometimes|nullable|' . self::IMAGE_RULE,
            'is_available' => 'sometimes|boolean',
        ]);

        // New file chosen → store it and delete the previous one.
        // No file sent → keep the existing image untouched.
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('menu-items', 'public');
            $this->deleteImage($current->image);
        } else {
            unset($data['image']);
        }

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
        $current = DB::table($this->table)
            ->where('id', $id)
            ->first();

        if (!$current) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }

        DB::table($this->table)->where('id', $id)->delete();
        $this->deleteImage($current->image);

        return response()->json(['message' => 'Menu item deleted successfully']);
    }

    // Remove a stored file; ignores empty values and old external URLs
    private function deleteImage(?string $path): void
    {
        if ($path && !preg_match('#^https?://#i', $path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
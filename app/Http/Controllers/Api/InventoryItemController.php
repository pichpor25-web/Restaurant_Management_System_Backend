<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryItemController extends Controller
{
    // GET /api/inventory (List stock with low-stock status)
    public function index(): JsonResponse
    {
        $items = DB::table('inventory_items')
            ->select(
                'id',
                'name',
                'unit',
                'quantity',
                'reorder_level',
                'unit_cost',
                'is_active',
                DB::raw('(quantity <= reorder_level) as is_low_stock')
            )
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($items);
    }

    public function show(int $id): JsonResponse
    {
        $item = DB::table('inventory_items')->where('id', $id)->first();

        if ($item === null) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }

        return response()->json($item);
    }

    // POST /api/inventory (Add new ingredient / stock item)
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'unit' => 'required|string|max:30',
            'quantity' => 'required|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $now = Carbon::now();

        $id = DB::table('inventory_items')->insertGetId([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'quantity' => $validated['quantity'],
            'reorder_level' => $validated['reorder_level'] ?? 0.00,
            'unit_cost' => $validated['unit_cost'] ?? 0.00,
            'is_active' => $validated['is_active'] ?? true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $item = DB::table('inventory_items')->where('id', $id)->first();

        return response()->json(['message' => 'Inventory item added', 'data' => $item], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = DB::table('inventory_items')->where('id', $id)->first();

        if ($item === null) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'unit' => ['sometimes', 'string', 'max:30'],
            'quantity' => ['sometimes', 'numeric', 'min:0'],
            'reorder_level' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unit_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        DB::table('inventory_items')
            ->where('id', $id)
            ->update([
                ...$validated,
                'updated_at' => Carbon::now(),
            ]);

        $updatedItem = DB::table('inventory_items')->where('id', $id)->first();

        return response()->json([
            'message' => 'Inventory item updated',
            'data' => $updatedItem,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = DB::table('inventory_items')->where('id', $id)->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }

        return response()->json(['message' => 'Inventory item deleted']);
    }

    // POST /api/inventory/{id}/adjust (Stock in / stock out / manual adjustment)
    public function adjustStock(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:stock_in,stock_out,adjustment',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $userId = $request->user()->id ?? 1;
        $now = Carbon::now();

        DB::transaction(function () use ($id, $validated, $userId, $now) {
            // 1. Record stock transaction
            DB::table('stock_transactions')->insert([
                'inventory_item_id' => $id,
                'user_id' => $userId,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 2. Update stock balance in inventory_items
            if ($validated['type'] === 'adjustment') {
                DB::table('inventory_items')
                    ->where('id', $id)
                    ->update([
                        'quantity' => $validated['quantity'],
                        'updated_at' => $now,
                    ]);
            } else {
                $operator = ($validated['type'] === 'stock_in') ? '+' : '-';
                DB::table('inventory_items')
                    ->where('id', $id)
                    ->update([
                        'quantity' => DB::raw("quantity {$operator} {$validated['quantity']}"),
                        'updated_at' => $now,
                    ]);
            }
        });

        $item = DB::table('inventory_items')->where('id', $id)->first();

        return response()->json(['message' => 'Stock adjusted successfully', 'data' => $item]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    private string $table = 'order_items';

    // GET /api/order-items
    public function index()
    {
        $items = DB::table($this->table)
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($items);
    }

    // POST /api/order-items
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'menu_item_id' => 'required|integer|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data['subtotal'] = round(
            $data['quantity'] * $data['unit_price'],
            2
        );
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table($this->table)->insertGetId($data);

        $item = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return response()->json($item, 201);
    }

    // GET /api/order-items/{id}
    public function show(string $id)
    {
        $item = DB::table($this->table)
            ->where('id', $id)
            ->first();

        if (!$item) {
            return response()->json([
                'message' => 'Order item not found',
            ], 404);
        }

        return response()->json($item);
    }

    // PUT /api/order-items/{id}
    public function update(Request $request, string $id)
    {
        $item = DB::table($this->table)
            ->where('id', $id)
            ->first();

        if (!$item) {
            return response()->json([
                'message' => 'Order item not found',
            ], 404);
        }

        $data = $request->validate([
            'order_id' => 'sometimes|required|integer|exists:orders,id',
            'menu_item_id' => 'sometimes|required|integer|exists:menu_items,id',
            'quantity' => 'sometimes|required|integer|min:1',
            'unit_price' => 'sometimes|required|numeric|min:0',
            'notes' => 'sometimes|nullable|string',
        ]);

        $quantity = $data['quantity'] ?? $item->quantity;
        $unitPrice = $data['unit_price'] ?? $item->unit_price;

        $data['subtotal'] = round($quantity * $unitPrice, 2);
        $data['updated_at'] = now();

        DB::table($this->table)
            ->where('id', $id)
            ->update($data);

        $updatedItem = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return response()->json($updatedItem);
    }

    // DELETE /api/order-items/{id}
    public function destroy(string $id)
    {
        $deleted = DB::table($this->table)
            ->where('id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Order item not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Order item deleted successfully',
        ]);
    }
}

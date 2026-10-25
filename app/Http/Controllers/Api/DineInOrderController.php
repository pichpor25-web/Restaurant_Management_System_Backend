<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DineInOrderController extends Controller
{
    private string $table = 'dine_in_orders';

    // GET /api/dine-in-orders
    public function index()
    {
        $items = DB::table($this->table)
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($items);
    }

    // POST /api/dine-in-orders
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
                Rule::unique('dine_in_orders', 'order_id'),
            ],
            'table_id' => 'required|integer|exists:restaurant_tables,id',
        ]);

        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table($this->table)->insertGetId($data);

        $item = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return response()->json($item, 201);
    }

    // GET /api/dine-in-orders/{id}
    public function show(string $id)
    {
        $item = DB::table($this->table)
            ->where('id', $id)
            ->first();

        if (!$item) {
            return response()->json([
                'message' => 'Dine-in order not found',
            ], 404);
        }

        return response()->json($item);
    }

    // PUT /api/dine-in-orders/{id}
    public function update(Request $request, string $id)
    {
        $exists = DB::table($this->table)
            ->where('id', $id)
            ->exists();

        if (!$exists) {
            return response()->json([
                'message' => 'Dine-in order not found',
            ], 404);
        }

        $data = $request->validate([
            'order_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:orders,id',
                Rule::unique('dine_in_orders', 'order_id')->ignore($id),
            ],
            'table_id' => 'sometimes|required|integer|exists:restaurant_tables,id',
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

    // DELETE /api/dine-in-orders/{id}
    public function destroy(string $id)
    {
        $deleted = DB::table($this->table)
            ->where('id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Dine-in order not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Dine-in order deleted successfully',
        ]);
    }
}
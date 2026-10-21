<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    private string $table = 'orders';

    // GET /api/orders
    public function index()
    {
        $orders = DB::table($this->table)
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($orders);
    }

    // POST /api/orders
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('orders', 'order_number'),
            ],
            'user_id' => 'nullable|integer|exists:users,id',
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'status' => 'nullable|string|max:30',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table($this->table)->insertGetId($data);

        $order = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return response()->json($order, 201);
    }

    // GET /api/orders/{id}
    public function show(string $id)
    {
        $order = DB::table($this->table)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    // PUT /api/orders/{id}
    public function update(Request $request, string $id)
    {
        $exists = DB::table($this->table)
            ->where('id', $id)
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $data = $request->validate([
            'order_number' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('orders', 'order_number')->ignore($id),
            ],
            'user_id' => 'sometimes|nullable|integer|exists:users,id',
            'order_type' => 'sometimes|required|in:dine_in,takeaway,delivery',
            'status' => 'sometimes|nullable|string|max:30',
            'subtotal' => 'sometimes|required|numeric|min:0',
            'discount' => 'sometimes|nullable|numeric|min:0',
            'tax' => 'sometimes|nullable|numeric|min:0',
            'total' => 'sometimes|required|numeric|min:0',
            'notes' => 'sometimes|nullable|string',
        ]);

        $data['updated_at'] = now();

        DB::table($this->table)
            ->where('id', $id)
            ->update($data);

        $order = DB::table($this->table)
            ->where('id', $id)
            ->first();

        return response()->json($order);
    }

    // DELETE /api/orders/{id}
    public function destroy(string $id)
    {
        $deleted = DB::table($this->table)
            ->where('id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
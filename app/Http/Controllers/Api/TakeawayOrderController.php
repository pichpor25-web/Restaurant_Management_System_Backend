<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TakeawayOrderController extends Controller
{
    private string $table = 'takeaway_orders';

    // GET /api/takeaway-orders
    public function index()
    {
        $items = DB::table($this->table)
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($items);
    }

    // POST /api/takeaway-orders
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
                Rule::unique('takeaway_orders', 'order_id'),
            ],
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'required|string|max:20',
        ]);

        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table($this->table)->insertGetId($data);

        $item = DB::table($this->table)->where('id', $id)->first();

        return response()->json($item, 201);
    }

    // GET /api/takeaway-orders/{id}
    public function show(string $id)
    {
        $item = DB::table($this->table)->where('id', $id)->first();

        if (!$item) {
            return response()->json(['message' => 'Takeaway order not found'], 404);
        }

        return response()->json($item);
    }

    // PUT /api/takeaway-orders/{id}
    public function update(Request $request, string $id)
    {
        $exists = DB::table($this->table)->where('id', $id)->exists();

        if (!$exists) {
            return response()->json(['message' => 'Takeaway order not found'], 404);
        }

        $data = $request->validate([
            'order_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:orders,id',
                Rule::unique('takeaway_orders', 'order_id')->ignore($id),
            ],
            'customer_name' => 'sometimes|required|string|max:100',
            'customer_phone' => 'sometimes|required|string|max:20',
        ]);

        $data['updated_at'] = now();

        DB::table($this->table)->where('id', $id)->update($data);

        return response()->json(
            DB::table($this->table)->where('id', $id)->first()
        );
    }

    // DELETE /api/takeaway-orders/{id}
    public function destroy(string $id)
    {
        $deleted = DB::table($this->table)->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Takeaway order not found'], 404);
        }

        return response()->json(['message' => 'Takeaway order deleted successfully']);
    }
}
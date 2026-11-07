<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    // លុប TAX_RATE និង SERVICE_FEE ចេញ

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_type' => 'required|in:dine_in,takeaway',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'customer_name' => 'required_if:order_type,takeaway|nullable|string|max:100',
            'customer_phone' => 'required_if:order_type,takeaway|nullable|string|max:20',
            'table_id' => 'required_if:order_type,dine_in|nullable|integer|exists:restaurant_tables,id', 
        ]);

        return DB::transaction(function () use ($data) {
            $menu = DB::table('menu_items')
                ->whereIn('id', collect($data['items'])->pluck('menu_item_id'))
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $lines = [];
            foreach ($data['items'] as $item) {
                $price = (float) $menu[$item['menu_item_id']]->price;
                $lineTotal = round($price * $item['quantity'], 2);
                $subtotal += $lineTotal;
                $lines[] = [
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    'subtotal' => $lineTotal,
                ];
            }

            $isDineIn = $data['order_type'] === 'dine_in';
            
            // កែត្រង់នេះ៖ ដក Tax និង Service ចេញ
            $tax = 0;
            $total = round($subtotal, 2); // Total ស្មើ Subtotal តែម្តង

            $table = null;
            if ($isDineIn) {
                $table = DB::table('restaurant_tables')
                    ->where('id', $data['table_id'])
                    ->where('status', 'available')
                    ->lockForUpdate()
                    ->first();

                if (!$table) {
                    abort(409, 'Selected table is no longer available'); 
                }

                DB::table('restaurant_tables')->where('id', $table->id)
                    ->update(['status' => 'occupied', 'updated_at' => now()]);
            }

            $orderId = DB::table('orders')->insertGetId([
                'order_number' => now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                'order_type' => $data['order_type'],
                'status' => 'pending',
                'subtotal' => round($subtotal, 2),
                'discount' => 0,
                'tax' => $tax, // ដាក់ 0
                'total' => $total, // ដាក់ត្រឹមតែ subtotal
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($lines as $line) {
                DB::table('order_items')->insert($line + [
                    'order_id' => $orderId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($isDineIn) {
                DB::table('dine_in_orders')->insert([
                    'order_id' => $orderId,
                    'table_id' => $table->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('takeaway_orders')->insert([
                    'order_id' => $orderId,
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'order' => DB::table('orders')->where('id', $orderId)->first(),
                'table' => $table,
            ], 201);
        });
    }
}
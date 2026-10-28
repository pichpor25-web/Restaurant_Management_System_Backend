<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TakeawayOrder;
use Illuminate\Database\Seeder;

class BakongTestOrderSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::updateOrCreate(
            ['name' => 'Bakong Test'],
            [
                'description' => 'Category for the seeded Bakong test order',
                'is_active' => true,
            ],
        );

        $menuItem = MenuItem::updateOrCreate(
            ['name' => 'Bakong Test Meal'],
            [
                'category_id' => $category->id,
                'description' => 'Menu item for testing takeaway checkout and payment',
                'price' => 0.01,
                'is_available' => true,
            ],
        );

        $quantity = 1;
        $unitPrice = (float) $menuItem->price;
        $subtotal = round($unitPrice * $quantity, 2);
        $tax = round($subtotal * 0.0, 0);

        $order = Order::updateOrCreate(
            ['order_number' => 'BAKONG-TEST-001'],
            [
                'user_id' => null,
                'order_type' => 'takeaway',
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'notes' => 'Seeded test order for Bakong KHQR payment',
            ],
        );

        OrderItem::updateOrCreate(
            [
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
            ],
            [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ],
        );

        TakeawayOrder::updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_name' => 'Bakong Test Customer',
                'customer_phone' => '0123456789',
            ],
        );

        $this->command?->info(
            sprintf('Bakong test order ready: ID %d, order %s, total $%.2f', $order->id, $order->order_number, $order->total),
        );
    }
}

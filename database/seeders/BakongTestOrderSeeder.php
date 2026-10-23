<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class BakongTestOrderSeeder extends Seeder
{
    public function run(): void
    {
        $order = Order::updateOrCreate(
            ['order_number' => 'BAKONG-TEST-001'],
            [
                'user_id' => null,
                'order_type' => 'takeaway',
                'status' => 'pending',
                'subtotal' => 1.00,
                'discount' => 0,
                'tax' => 0,
                'total' => 1.00,
                'notes' => 'Seeded test order for Bakong KHQR payment',
            ],
        );

        $this->command?->info(
            sprintf('Bakong test order ready: ID %d, order %s, total $%.2f', $order->id, $order->order_number, $order->total),
        );
    }
}

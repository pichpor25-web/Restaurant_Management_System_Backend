<?php

namespace Tests\Feature\Models;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_have_payments_attached(): void
    {
        $order = Order::create();

        $payment = $order->payments()->create([
            'amount' => '12.50',
            'method' => 'cash',
        ]);

        $this->assertSame($order->id, $payment->order->id);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'order_id' => $order->id,
        ]);
    }
}

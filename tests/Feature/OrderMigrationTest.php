<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_table_exists_before_payments_reference_it(): void
    {
        $this->assertTrue(Schema::hasTable('orders'));
        $this->assertTrue(Schema::hasTable('payments'));
    }
}

<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // These fields are created by ensure_orders_table_exists.
    }

    public function down(): void
    {
        // Keep the order schema owned by ensure_orders_table_exists.
    }
};

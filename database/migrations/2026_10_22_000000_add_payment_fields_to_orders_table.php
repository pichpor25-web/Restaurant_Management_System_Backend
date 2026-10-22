<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('order_number')->nullable()->unique();
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status', 30)->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['order_number']);
            $table->dropColumn(['order_number', 'total', 'status']);
        });
    }
};

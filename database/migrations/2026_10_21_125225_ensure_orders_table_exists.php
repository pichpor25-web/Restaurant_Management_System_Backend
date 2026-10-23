<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table): void {
                $table->id();
                $table->string('order_number', 30)->unique();
                $table->foreignId('user_id')->nullable()
                    ->constrained('users')->nullOnDelete();
                $table->string('order_type', 20);
                $table->string('status', 30)->default('pending');
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('tax', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'order_number')) {
                $table->string('order_number', 30)->nullable()->unique();
            }
            if (! Schema::hasColumn('orders', 'user_id')) {
                $table->foreignId('user_id')->nullable()
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type', 20)->default('takeaway');
            }
            if (! Schema::hasColumn('orders', 'status')) {
                $table->string('status', 30)->default('pending');
            }
            if (! Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'tax')) {
                $table->decimal('tax', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'total')) {
                $table->decimal('total', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->timestamps();

            $table->unique(['product_id', 'variant_id']);
            $table->index(['quantity', 'reserved_quantity']);
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('session_or_user_id')->index();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity');
            $table->enum('status', ['reserved', 'committed', 'released'])->default('reserved')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->enum('type', [
                'initial',
                'order_reservation',
                'reservation_expired',
                'order_deduction',
                'order_cancellation_restore',
                'return_restock',
                'manual_adjustment',
                'damaged_writeoff'
            ])->index();
            $table->integer('quantity_change');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->string('reference_type')->nullable(); // e.g. App\Models\Order, App\Models\ReturnRequest
            $table->string('reference_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Admin or actor
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('inventories');
    }
};

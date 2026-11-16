<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
            $table->enum('status', [
                'placed',
                'confirmed',
                'processing',
                'packed',
                'dispatched',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'cancelled'
            ])->default('placed')->index();
            $table->enum('payment_status', [
                'pending',
                'authorized',
                'paid',
                'failed',
                'partially_refunded',
                'refunded'
            ])->default('pending')->index();
            $table->enum('fulfillment_status', [
                'unfulfilled',
                'partially_fulfilled',
                'fulfilled'
            ])->default('unfulfilled')->index();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('shipping', 10, 2)->default(0.00);
            $table->decimal('tax', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->string('coupon_code')->nullable();
            $table->string('shipping_method_id')->default('standard');
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable()->index();
            $table->date('estimated_delivery')->nullable();
            $table->json('shipping_address');
            $table->json('billing_address');
            $table->string('payment_method');
            $table->string('payment_last4', 4)->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['created_at', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('product_sku');
            $table->string('product_slug');
            $table->string('selected_size')->nullable();
            $table->string('selected_color')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};


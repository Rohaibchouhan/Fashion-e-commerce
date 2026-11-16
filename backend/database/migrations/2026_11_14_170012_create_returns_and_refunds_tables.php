<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('rma_number')->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', [
                'requested',
                'under_review',
                'approved',
                'rejected',
                'pickup_scheduled',
                'in_transit',
                'received',
                'inspected',
                'refund_processing',
                'refunded',
                'exchange_processing',
                'completed'
            ])->default('requested')->index();
            $table->text('rejection_reason')->nullable();
            $table->enum('resolution', ['refund', 'exchange'])->default('refund');
            $table->text('exchange_details')->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0.00);
            $table->string('tracking_number')->nullable();
            $table->string('label_url')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->boolean('inspection_passed')->nullable();
            $table->boolean('restocked')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('selected_size')->nullable();
            $table->string('selected_color')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity');
            $table->enum('reason', [
                'wrong_size',
                'damaged_defective',
                'not_as_described',
                'quality_issue',
                'changed_mind',
                'received_wrong_item',
                'other'
            ]);
            $table->text('customer_comment')->nullable();
            $table->enum('inspection_result', ['pending', 'resellable', 'damaged', 'scrap'])->default('pending');
            $table->integer('restocked_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('return_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_item_id')->constrained('return_items')->cascadeOnDelete();
            $table->string('url');
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number')->unique();
            $table->foreignId('return_id')->nullable()->constrained('returns')->nullOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->enum('status', ['pending', 'approved', 'processed', 'rejected', 'failed'])->default('pending')->index();
            $table->string('gateway_refund_id')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('refund_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained('refunds')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('gateway');
            $table->string('reference')->nullable();
            $table->enum('status', ['success', 'pending', 'failed']);
            $table->json('response_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('original_order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('new_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('requested_size')->nullable();
            $table->string('requested_color')->nullable();
            $table->enum('status', ['pending', 'ordered', 'dispatched', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchanges');
        Schema::dropIfExists('refund_transactions');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('return_images');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
    }
};


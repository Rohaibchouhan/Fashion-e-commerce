<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'idempotency_hash')) {
                $table->string('idempotency_hash', 64)->nullable()->index()->after('idempotency_key');
            }
            if (!Schema::hasColumn('orders', 'guest_session_id')) {
                $table->string('guest_session_id', 100)->nullable()->index()->after('idempotency_hash');
            }
        });

        Schema::table('stock_reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_reservations', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('session_or_user_id')->constrained('orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_reservations', function (Blueprint $table) {
            if (Schema::hasColumn('stock_reservations', 'order_id')) {
                $table->dropForeign(['order_id']);
                $table->dropColumn('order_id');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'guest_session_id')) {
                $table->dropColumn('guest_session_id');
            }
            if (Schema::hasColumn('orders', 'idempotency_hash')) {
                $table->dropColumn('idempotency_hash');
            }
        });
    }
};

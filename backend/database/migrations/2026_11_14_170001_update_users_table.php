<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->enum('status', ['active', 'inactive', 'pending_activation', 'suspended'])->default('active')->after('password');
            $table->string('activation_token')->nullable()->after('status');
            $table->timestamp('deletion_requested_at')->nullable()->after('activation_token');
            $table->softDeletes()->after('updated_at');
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email', 'status']);
            $table->dropColumn(['phone', 'avatar', 'status', 'activation_token', 'deletion_requested_at', 'deleted_at']);
        });
    }
};


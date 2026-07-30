<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('core_user_id')->nullable()->unique()->after('id');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_synced_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['core_user_id']);
            $table->dropColumn(['core_user_id', 'is_active', 'last_synced_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the domain columns that web-shelf layered onto the base
     * users table (business_entity_id, job_title_id, whatsapp_number)
     * plus a simple single-role string column that replaces the former
     * spatie/laravel-permission setup.
     *
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 255)->nullable()->unique()->after('name');
            $table->string('whatsapp_number', 32)->nullable()->after('email');
            $table->foreignId('business_entity_id')->nullable()->after('email_verified_at')->constrained('business_entities')->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->after('business_entity_id')->constrained('job_titles')->nullOnDelete();
            $table->string('role')->nullable()->after('password');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
            $table->dropConstrainedForeignId('job_title_id');
            $table->dropConstrainedForeignId('business_entity_id');
            $table->dropColumn('whatsapp_number');
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Final folded schema for the division_approvers table, merging the
     * harden_asset_request_lifecycle change (user_id FK restrict + unique
     * division_id/user_id).
     *
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('division_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->integer('level');
            $table->timestamps();
            $table->unique(['division_id', 'user_id'], 'div_approvers_div_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('division_approvers');
    }
};

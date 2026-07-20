<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Final folded schema for the asset_requests table, merging
     * update_asset_requests_for_divisions, add_type_and_asset_id,
     * harden_asset_request_lifecycle (attachment -> text, user_id restrict),
     * add_fulfillment_tracking, add_public_token, and add_asset_location_id.
     *
     * Also wires the foreign key on assets.asset_request_id, whose column was
     * added in the assets migration without a constraint because this table did
     * not exist yet.
     *
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique()->nullable();
            $table->string('public_token', 64)->unique()->nullable();
            $table->string('type')->default('pengadaan');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('asset_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->string('item_name')->nullable();
            $table->integer('qty')->nullable();
            $table->integer('current_level')->default(1);
            $table->text('description')->nullable();
            $table->text('attachment')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->foreignId('fulfilled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asset_transfer_id')->nullable()->constrained('asset_transfers')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->foreign('asset_request_id')->references('id')->on('asset_requests')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['asset_request_id']);
        });

        Schema::dropIfExists('asset_requests');
    }
};

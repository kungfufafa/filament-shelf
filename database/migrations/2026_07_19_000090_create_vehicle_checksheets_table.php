<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Final folded schema for the vehicle_checksheets table, merging
     * add_destination.
     *
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_checksheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->string('pic')->nullable();
            $table->string('license_plate');
            $table->string('location')->nullable();
            $table->string('destination')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('start_km')->nullable();
            $table->dateTime('departure_time')->nullable();
            $table->text('departure_photo')->nullable();
            $table->text('departure_damage_report')->nullable();
            $table->integer('end_km')->nullable();
            $table->dateTime('return_time')->nullable();
            $table->text('return_photo')->nullable();
            $table->text('return_damage_report')->nullable();
            $table->float('rental_duration')->nullable();
            $table->float('distance_traveled')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_checksheets');
    }
};

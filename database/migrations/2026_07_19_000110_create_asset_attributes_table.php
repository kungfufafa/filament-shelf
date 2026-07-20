<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Final folded schema for the asset_attributes table. The
     * expand_asset_attribute_value_for_document_payloads migration changed
     * attribute_value from string to text, which is reflected here.
     *
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('custom_attribute_id')->nullable()->constrained('custom_asset_attributes')->nullOnDelete();
            $table->text('attribute_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_attributes');
    }
};

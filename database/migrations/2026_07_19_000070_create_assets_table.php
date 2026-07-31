<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Final folded schema for the assets table, merging every ALTER that
     * touched it in web-shelf: add_qty, add_image, add_condition_and_nbh_fields,
     * add_sale_audit_fields, and the asset_request_id link from
     * harden_asset_request_lifecycle. The foreign key on asset_request_id is
     * added in the asset_requests migration because that table does not exist
     * yet at this point in the dependency order.
     *
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->date('purchase_date')->nullable();
            $table->foreignId('business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('audit_document_path')->nullable();
            $table->string('nbh_document_path')->nullable();
            $table->text('nbh_notes')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('type')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('imei1')->nullable();
            $table->string('imei2')->nullable();
            $table->bigInteger('item_price')->nullable();
            $table->foreignId('asset_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->string('condition_status')->default('available');
            $table->string('nbh_status')->default('none');
            $table->date('nbh_reported_at')->nullable();
            $table->integer('qty')->default(1);
            $table->boolean('is_available')->default(true);
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
            $table->foreignId('nbh_responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asset_request_id')->nullable();
            $table->date('sold_at')->nullable();
            $table->string('sold_to')->nullable();
            $table->bigInteger('sold_price')->nullable();
            $table->string('sale_document_path')->nullable();
            $table->text('sale_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

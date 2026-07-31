<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Final folded schema for the custom_asset_attributes table, merging
     * add_notification_recipients.
     *
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('custom_asset_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->boolean('required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('category_id')->nullable();
            $table->boolean('is_notifiable')->default(false);
            $table->enum('notification_type', ['fixed_date', 'relative_date', 'monthly'])->nullable();
            $table->integer('notification_offset')->nullable();
            $table->date('fixed_notification_date')->nullable();
            $table->json('notification_channels')->nullable();
            $table->json('notification_recipient_user_ids')->nullable();
            $table->json('notification_recipient_emails')->nullable();
            $table->json('notification_recipient_whatsapp_numbers')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_asset_attributes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Final folded schema for the tasks table, merging add_upload_dokumen,
     * add_work_timestamp, and add_user_id.
     *
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('business_entity_id')->constrained('business_entities');
            $table->timestamp('work_timestamp');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->decimal('cost', 12, 2);
            $table->string('location');
            $table->enum('status', ['open', 'in_progress', 'completed'])->default('open');
            $table->string('attachment')->nullable();
            $table->string('document_upload')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

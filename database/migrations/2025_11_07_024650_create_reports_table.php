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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            
            // Polymorphic relationship untuk report user atau post
            $table->morphs('reportable'); // reportable_type, reportable_id
            
            // Alasan report (string untuk fleksibilitas, validasi di model)
            $table->string('reason');
            
            $table->text('description')->nullable(); // Detail tambahan
            $table->enum('status', ['pending', 'reviewed', 'resolved', 'dismissed'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            
            // Note: morphs() sudah otomatis membuat index untuk reportable_type dan reportable_id
            // foreignId()->constrained() sudah otomatis membuat index untuk reporter_id
            $table->index('status');
            $table->index('created_at');
            
            // Prevent duplicate reports (user tidak bisa report same item berkali-kali)
            $table->unique(['reporter_id', 'reportable_type', 'reportable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

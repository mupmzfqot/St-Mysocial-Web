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
            $table->enum('account_status', ['active', 'deletion_requested', 'deleted'])->default('active')->after('is_active');
            $table->timestamp('deletion_requested_at')->nullable()->after('account_status');
            $table->timestamp('scheduled_deletion_at')->nullable()->after('deletion_requested_at');
            $table->text('deletion_reason')->nullable()->after('scheduled_deletion_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_status', 'deletion_requested_at', 'scheduled_deletion_at', 'deletion_reason']);
        });
    }
};

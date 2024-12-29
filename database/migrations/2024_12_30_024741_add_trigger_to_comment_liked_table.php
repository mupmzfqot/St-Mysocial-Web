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
        Schema::table('comment_liked', function (Blueprint $table) {
            // Trigger to increment likes count after a new likes is added
            DB::statement("
                CREATE TRIGGER after_comment_liked_insert
                AFTER INSERT ON comment_likeds
                FOR EACH ROW
                BEGIN
                    UPDATE comments
                    SET like_count = liked_count + 1
                    WHERE id = NEW.comment_id;
                END;
            ");

            // Trigger to decrement likes count after a likes is deleted
            DB::statement("
                CREATE TRIGGER after_comment_liked_delete
                AFTER DELETE ON comment_likeds
                FOR EACH ROW
                BEGIN
                    UPDATE comments
                    SET like_count = liked_count - 1
                    WHERE id = OLD.comment_id;
                END;
            ");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comment_liked', function (Blueprint $table) {
            //
        });
    }
};

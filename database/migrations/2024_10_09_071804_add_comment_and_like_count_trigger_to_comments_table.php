<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Trigger to increment comment count after a new comment is added
            DB::statement("
                CREATE TRIGGER after_comment_insert
                AFTER INSERT ON comments
                FOR EACH ROW
                BEGIN
                    UPDATE posts
                    SET comment_count = comment_count + 1
                    WHERE id = NEW.post_id;
                END;
            ");

            // Trigger to decrement comment count after a comment is deleted
            DB::statement("
                CREATE TRIGGER after_comment_delete
                AFTER DELETE ON comments
                FOR EACH ROW
                BEGIN
                    UPDATE posts
                    SET comment_count = comment_count - 1
                    WHERE id = OLD.post_id;
                END;
            ");
        });

        Schema::table('post_liked', function (Blueprint $table) {
            // Trigger to increment likes count after a new likes is added
            DB::statement("
                CREATE TRIGGER after_post_liked_insert
                AFTER INSERT ON post_likeds
                FOR EACH ROW
                BEGIN
                    UPDATE posts
                    SET like_count = like_count + 1
                    WHERE id = NEW.post_id;
                END;
            ");

            // Trigger to decrement likes count after a likes is deleted
            DB::statement("
                CREATE TRIGGER after_post_liked_delete
                AFTER DELETE ON post_likeds
                FOR EACH ROW
                BEGIN
                    UPDATE posts
                    SET like_count = like_count - 1
                    WHERE id = OLD.post_id;
                END;
            ");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the triggers if the migration is rolled back
        DB::statement("DROP TRIGGER IF EXISTS after_comment_insert");
        DB::statement("DROP TRIGGER IF EXISTS after_comment_delete");
        DB::statement("DROP TRIGGER IF EXISTS after_post_liked_insert");
        DB::statement("DROP TRIGGER IF EXISTS after_post_liked_delete");
    }
};

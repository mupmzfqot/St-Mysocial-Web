<?php

namespace App\Console\Commands;

use App\Actions\Migrations\Messages;
use App\Actions\Migrations\PostComments;
use App\Actions\Migrations\PostLikes;
use App\Actions\Migrations\Posts;
use App\Actions\Migrations\Users;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunDataMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-data-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute all data migrations from old database';

    public function handle(): void
    {
        $this->info('Starting data migration');

        try {
            // Run migrations step by step
            $this->runMigration('Users migration', new Users(), 'users');
            $this->runMigration('Posts migration', new Posts(), 'posts');
            $this->runMigration('Post Likes migration', new PostLikes());
            $this->runMigration('Post Comments migration', new PostComments(), 'comments');
            $this->runMigration('Chat Messages migration', new Messages(), 'chats');

            $this->info('Data migration complete!');
        } catch (\Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
        }
    }

    /**
     * Runs a migration step and optionally fixes auto-increment.
     *
     * @param string $stepDescription
     * @param object $migrationClass
     * @param string|null $tableName
     */
    private function runMigration(string $stepDescription, object $migrationClass, ?string $tableName = null): void
    {
        try {
            $this->info("$stepDescription running...");
            $migrationClass->handle();

            if ($tableName) {
                Artisan::call('fix:autoincrement', ['table' => $tableName]);
                $this->info("$stepDescription complete and auto-increment fixed for table: $tableName");
            } else {
                $this->info("$stepDescription complete!");
            }
        } catch (\Exception $e) {
            logger($e->getMessage());
            $this->error("Error in $stepDescription: " . $e->getMessage());
        }
    }
}

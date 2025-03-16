<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAutoIncrement extends Command
{
    /**
     * Execute the console command.
     */
    protected $signature = 'fix:autoincrement {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix the AUTO_INCREMENT value for a given table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');

        try {
            // Get the max ID from the specified table
            $maxId = DB::connection('mysql')->table($table)->max('id');

            if (is_null($maxId)) {
                $this->info("Table '{$table}' is empty. AUTO_INCREMENT remains unchanged.");
                return;
            }

            // Set AUTO_INCREMENT to max ID + 1
            $nextId = $maxId + 1;
            DB::connection('mysql')->statement("ALTER TABLE {$table} AUTO_INCREMENT = {$nextId}");

            $this->info("AUTO_INCREMENT for table '{$table}' has been set to {$nextId}.");
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }
}

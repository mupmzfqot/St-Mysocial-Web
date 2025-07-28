<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateRandomPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:generate-passwords {--dry-run : Show what would be changed without actually updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate random hash passwords for all users except admins';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get all users except those with admin role
        $users = User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        if ($users->isEmpty()) {
            $this->info('No non-admin users found.');
            return;
        }

        $this->info("Found {$users->count()} non-admin users to update:");
        $this->newLine();

        $passwords = [];
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            // Generate random password (8-12 characters)
            $randomPassword = Str::random(rand(8, 12));
            $hashedPassword = Hash::make($randomPassword);

            // Store for display
            $passwords[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'new_password' => $randomPassword,
            ];

            if (!$isDryRun) {
                // Update user password
                $user->update([
                    'password' => $hashedPassword,
                    'password_changed_at' => now(), // Optional: track when password was changed
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display results in a table
        $this->table(
            ['ID', 'Name', 'Email', 'New Password'],
            collect($passwords)->map(function ($item) {
                return [
                    $item['id'],
                    $item['name'],
                    $item['email'],
                    $item['new_password'],
                ];
            })->toArray()
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN - no passwords were actually changed.');
            $this->info('💡 Run without --dry-run to actually update the passwords.');
        } else {
            $this->newLine();
            $this->info("✅ Successfully updated passwords for {$users->count()} users.");
            $this->warn('⚠️  IMPORTANT: Save the passwords above as users will need them to login.');
            
            // Optionally save to file
            if ($this->confirm('Do you want to save these passwords to a file?')) {
                $this->savePasswordsToFile($passwords);
            }
        }
    }

    /**
     * Save passwords to a CSV file
     */
    private function savePasswordsToFile($passwords)
    {
        $filename = 'user_passwords_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/' . $filename);

        $file = fopen($filepath, 'w');
        
        // Add CSV header
        fputcsv($file, ['ID', 'Name', 'Email', 'New Password', 'Generated At']);
        
        // Add data rows
        foreach ($passwords as $password) {
            fputcsv($file, [
                $password['id'],
                $password['name'],
                $password['email'],
                $password['new_password'],
                now()->format('Y-m-d H:i:s')
            ]);
        }
        
        fclose($file);
        
        $this->info("💾 Passwords saved to: {$filepath}");
        $this->warn("🔒 Keep this file secure and delete it after distributing passwords!");
    }
} 
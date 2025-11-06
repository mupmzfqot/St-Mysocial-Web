<?php

namespace App\Console\Commands;

use App\Services\AccountDeletionService;
use Illuminate\Console\Command;

class ProcessScheduledAccountDeletions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:process-deletions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process permanent account deletions for users whose grace period has expired';

    protected $deletionService;

    public function __construct(AccountDeletionService $deletionService)
    {
        parent::__construct();
        $this->deletionService = $deletionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled account deletions...');

        $count = $this->deletionService->processPermanentDeletions();

        if ($count > 0) {
            $this->info("Successfully processed {$count} account deletion(s).");
        } else {
            $this->info('No accounts to delete.');
        }

        return Command::SUCCESS;
    }
}

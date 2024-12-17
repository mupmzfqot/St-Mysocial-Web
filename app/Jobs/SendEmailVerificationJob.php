<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailVerificationJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->notify(new VerifyEmail);
    }

    public function failed(\Throwable $exception): void
    {
        // Log the failure
        \Log::error('Email verification failed for user: ' . $this->user->id, [
            'error' => $exception->getMessage(),
            'user' => $this->user->email
        ]);
    }
}

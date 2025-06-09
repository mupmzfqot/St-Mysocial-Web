<?php
namespace App\Jobs;

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastFcmNotification implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected array $tokens;
    protected string $title;
    protected string $body;
    protected array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $tokens, string $title, string $body, array $data = [])
    {
        $this->tokens = $tokens;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $fcmService)
    {
        $fcmService->SendNotificationToMultipleTokens($this->tokens, $this->title, $this->body, $this->data);
    }
}

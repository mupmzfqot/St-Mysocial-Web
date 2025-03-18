<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversation_id,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('message.notification')
        ];
    }

    public function broadcastWith(): array
    {
       
        return [
            'users_id' => $this->getRecipients(),
        ];
    }

    private function getRecipients(): array
    {
        $conversation = Conversation::query()
                ->where('id', $this->conversation_id)
                ->with('users', function ($query) {
                    $query->where('user_id', '!=', auth()->id());
                })
                ->first();
        return array_column($conversation->users->toArray(),'id');
    }

}

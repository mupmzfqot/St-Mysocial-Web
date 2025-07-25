<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Log;

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
       
        return $this->getRecipients();
    }

    private function getRecipients(): array
    {
        $conversation = Conversation::query()
                ->where('id', $this->conversation_id)
                ->with([
                    'users' => function ($query) {
                        $query->where('user_id', '!=', auth()->id())
                            ->select('users.id', 'users.name');
                    },
                    'messages' => function ($query) {
                        $query->latest()->take(1);
                    }
                ])
                ->first();

        if (!$conversation) {
            Log::warning('Conversation not found', ['conversation_id' => $this->conversation_id]);
            return [
                'user_ids' => [],
                'message' => [
                    'content' => null,
                    'sender_id' => null,
                    'receiver_id' => null,
                    'created_at' => null,
                    'conversation_id' => $this->conversation_id
                ]
            ];
        }

        if ($conversation->messages->isEmpty()) {
            Log::warning('No messages found in conversation', ['conversation_id' => $this->conversation_id]);
            return [
                'user_ids' => [],
                'message' => [
                    'content' => null,
                    'sender_id' => null,
                    'receiver_id' => null,
                    'created_at' => null,
                    'conversation_id' => $this->conversation_id
                ]
            ];
        }
    
        $latestMessage = $conversation->messages->first();
        
        Log::info('Broadcasting NewMessage event', [
            'conversation_id' => $this->conversation_id,
            'message_id' => $latestMessage->id,
            'sender_id' => $latestMessage->sender_id,
            'content' => $latestMessage->content
        ]);
        
        return [
            'user_ids' => $conversation->users->pluck('id')->toArray(),
            'message' => [
                'content' => $latestMessage->content,
                'sender_id' => $latestMessage->sender_id,
                'receiver_id' => $latestMessage->receiver_id,
                'created_at' => $latestMessage->created_at,
                'conversation_id' => $this->conversation_id
            ]
        ];
    }

}

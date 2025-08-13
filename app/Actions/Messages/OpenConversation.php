<?php

namespace App\Actions\Messages;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class OpenConversation
{
    public function handle($user_id, $auth_id)
    {
        $authUserId = $auth_id;
        $user = User::find($user_id);

        $conversation = Conversation::query()
            ->where('type', 'private')
            ->whereHas('users', function ($query) use ($authUserId, $user_id) {
                $query->whereIn('user_id', [$authUserId, $user_id]);
            }, '=', 2)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::query()->create(['type' => 'private']);
            $conversation->users()->attach([$authUserId, $user_id], ['joined_at' => now()]);
        }

        $markAsRead = $conversation->messages()->where('sender_id', '!=', $authUserId)->update(['is_read' => true]);

        $messages = $conversation->messages()
            ->with('sender', 'media')
            ->orderBy('created_at')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'content' => $message->content,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name,
                    'media' => $message->getMedia('message_media'),
                ];
            });

        Gate::authorize('view', $conversation);

        return [
            'conversation' => $conversation,
            'messages' => $messages,
        ];
    }
}

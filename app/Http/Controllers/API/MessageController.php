<?php

namespace App\Http\Controllers\API;

use App\Actions\Messages\OpenConversation;
use App\Actions\Messages\SendMessage;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;    

class MessageController extends Controller
{
    public function conversationList(Request $request)
    {
        $currentUserId = $request->user()->id;

        $results = Conversation::query()
            ->where('type', 'private')
            ->whereHas('users', function ($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId);
            })
            ->whereHas('messages')
            ->withCount('users')
            ->having('users_count', 2)
            ->with([
                'users' => function ($query) use ($currentUserId) {
                    $query->where('user_id', '!=', $currentUserId)
                        ->select('users.id', 'name');
                },
                'messages' => function ($query) {
                    $query->latest()->limit(1);
                }
            ])
            ->get();

        $conversation = $results->map(function ($conversation) use ($request) {
            return [
                'id' => $conversation->id,
                'user_id' => $conversation->users->first()->id,
                'name' => $conversation->users->first()->name,
                'avatar' => $conversation->users->first()->avatar,
                'content' => $conversation->messages,
                'unread_message_count' => $conversation->unreadMessagesCount($request->user()->id), 
            ];
        });

        return response()->json([
            'error' => 0,
            'unread_conversation_count' => $conversation->filter(function ($value) {
                return $value['unread_message_count'] > 0;
            })->count(),
            'data' => $conversation
        ]);
    }

    public function getConversation(Request $request, OpenConversation $openConversation)
    {
        try {
            $request->validate([
                'recipient_id' => 'required|integer|exists:users,id'
            ], [
                'recipient_id.exists' => 'User not exists'
            ]);

            $authUserId = $request->user()->id;
            $recipientId = $request->recipient_id;

            $results = $openConversation->handle($recipientId, $authUserId);

            return response()->json([
                'error' => 0,
                'conversation_id' => $results['conversation']->id,
                'data' => $results['messages']
            ]);
        } Catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function markAsRead($conversation_id, Request $request)
    {
        $conversation = Conversation::query()->find($conversation_id);
        Gate::authorize('view', $conversation);

        $conversation->messages()
            ->where('is_read', false)
            ->whereNot('sender_id', $request->user()->id)
            ->update(['is_read' => true]);

        return response()->json([
            'error' => 0,
            'message' => 'Marked as read'
        ]);
    }

    public function sendMessage(Request $request, SendMessage $sendMessage)
    {
        return $sendMessage->handle($request, $request->conversation_id);
    }

    private function formatResult($message, $recipient_id)
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'content' => $message->content,
            'sender_id' => $message->sender_id,
            'recipient' => new UserResource(User::find($recipient_id)),
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender?->name,
            'media' => array_values($message->getMedia('message_media')->toArray())
        ];
    }
}

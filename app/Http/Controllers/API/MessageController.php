<?php

namespace App\Http\Controllers\API;

use App\Actions\Messages\OpenConversation;
use App\Actions\Messages\SendMessage;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Conversation;
use App\Models\User;
use App\Models\UserBlock;
use App\Traits\HasBlockedUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    use HasBlockedUsers;
    public function conversationList(Request $request)
    {
        $currentUserId = $request->user()->id;
        $blockedUserIds = $this->getBlockedUserIds($currentUserId);

        $results = Conversation::query()
            ->where('type', 'private')
            ->whereNull('deleted_at') // Exclude soft deleted conversations
            ->whereHas('users', function ($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId);
            })
            ->whereHas('messages')
            ->withCount('users')
            ->having('users_count', 2)
            ->with([
                'users' => function ($query) use ($currentUserId, $blockedUserIds) {
                    $query->where('user_id', '!=', $currentUserId)
                        ->when(!empty($blockedUserIds), function($q) use ($blockedUserIds) {
                            $q->whereNotIn('user_id', $blockedUserIds);
                        })
                        ->select('users.id', 'name');
                },
                'messages' => function ($query) {
                    $query->latest()->limit(1);
                }
            ])
            ->get();

        // Filter out conversations with blocked users
        $results = $results->filter(function ($conversation) use ($blockedUserIds) {
            $otherUser = $conversation->users->first();
            return $otherUser && !in_array($otherUser->id, $blockedUserIds);
        });

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

            // Check if recipient is blocked
            $blockedUserIds = $this->getBlockedUserIds($authUserId);
            if (in_array($recipientId, $blockedUserIds)) {
                return response()->json([
                    'error' => 1,
                    'message' => 'User not found'
                ], 404);
            }

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

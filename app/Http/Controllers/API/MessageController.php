<?php

namespace App\Http\Controllers\API;

use App\Actions\Messages\OpenConversation;
use App\Events\MessageSent;
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
            ->withCount('users')
            ->having('users_count', 2)
            ->with([
                'users' => function ($query) use ($currentUserId) {
                    $query->where('user_id', '!=', $currentUserId)
                        ->select('users.id', 'name');
                },
                'messages' => function ($query) {
                    $query->latest()->first();
                }
            ])
            ->get();

        $conversation = $results->map(function ($conversation) {
            return [
                'id' => $conversation->id,
                'user_id' => $conversation->users->first()->id,
                'name' => $conversation->users->first()->name,
                'avatar' => $conversation->users->first()->avatar,
                'latest_message' => $conversation->messages,
            ];
        });

        return response()->json([
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

    public function sendMessage(Request $request)
    {
        try {
            $conversation = Conversation::query()->find($request->conversation_id);
            Gate::authorize('send', $conversation);

            $message = $conversation->messages()->create([
                'sender_id' => auth()->id(),
                'content' => $request->message,
            ]);

//            broadcast(new MessageSent($message));

            return response()->json([
                'error' => 0,
                'data' => $this->formatResult($message, $request->recipient_id),
            ]);
        } Catch (\Exception $exception) {
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage(),
            ]);
        }

    }

    private function formatResult($message, $recipient_id)
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'content' => $message->content,
            'sender_id' => $message->sender_id,
            'recipient' => new UserResource(User::find($recipient_id))
        ];
    }
}

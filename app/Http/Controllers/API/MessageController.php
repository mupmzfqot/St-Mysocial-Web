<?php

namespace App\Http\Controllers\API;

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

    public function getConversation(Request $request)
    {
        $authUserId = $request->user()->id;
        $recipientId = $request->recipient_id;

        $conversation = Conversation::query()
            ->where('type', 'private')
            ->whereHas('users', function ($query) use ($authUserId, $recipientId) {
                $query->whereIn('user_id', [$authUserId, $recipientId]);
            }, '=', 2)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::query()->create(['type' => 'private']);
            $conversation->users()->attach([$authUserId, $recipientId], ['joined_at' => now()]);
        }

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->map(function ($message) use($recipientId) {
                return $this->formatResult($message, $recipientId);
            });

        return response()->json([
            'data' => $messages
        ]);

    }

    public function sendMessage(Request $request)
    {
        $conversation = Conversation::query()->find($request->conversation_id);
        Gate::authorize('send', $conversation);

        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'content' => $request->message,
        ]);

        broadcast(new MessageSent($message));

        return response()->json([
            'data' => $this->formatResult($message, $request->recipient_id),
        ]);

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

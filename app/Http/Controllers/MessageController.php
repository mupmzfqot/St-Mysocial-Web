<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index()
    {
        $currentUserId = auth()->id();

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

        $conversations = $results->map(function ($conversation) {
            return [
                'id' => $conversation->id,
                'user_id' => $conversation->users->first()->id,
                'name' => $conversation->users->first()->name,
                'avatar' => $conversation->users->first()->avatar,
                'latest_message' => $conversation->messages,
                'unread_messages_count' => $conversation->messages->where('read', false)->where('sender_id', '!=', auth()->id())->count(),
            ];
        });

        return Inertia::render('Messages/Index', compact('conversations'));
    }

    public function openConversation(Request $request, $user_id)
    {
        $authUserId = auth()->id();
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

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'content' => $message->content,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name,

                ];
            });

        Gate::authorize('view', $conversation);

        return inertia('Messages/Show', compact('conversation', 'messages', 'user'));
    }

    public function markAsRead($conversation_id, Request $request)
    {
        $conversation = Conversation::query()->find($conversation_id);
        Gate::authorize('view', $conversation);

        $conversation->messages()
            ->where('is_read', false)
            ->whereNot('sender_id', auth()->id())
            ->update(['is_read' => true]);

        return response()->noContent();
    }

    public function sendMessage(Request $request, $conversation_id)
    {
        $conversation = Conversation::query()->find($conversation_id);
        Gate::authorize('send', $conversation);

        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'content' => $request->message,
        ]);

        broadcast(new MessageSent($message));

        return response()->json([
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'content' => $message->content,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
        ]);

    }

    public function getUnreadCount()
    {
        $currentUserId = auth()->id();

        $unreadCount = Conversation::query()
            ->select('id', 'type', 'name')
            ->where('type', 'private')
            ->whereHas('users', function ($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId);
            })
            ->whereHas('messages', function ($query) use ($currentUserId) {
                $query->where('is_read', false)
                    ->where('sender_id', '!=', $currentUserId);
            })
            ->withCount(['messages' => function ($query) use ($currentUserId) {
                $query->where('is_read', false)
                    ->where('sender_id', '!=', $currentUserId);
            }])
            ->get();

        return response()->json([
            'conversations' => $unreadCount,
            'total' => $unreadCount->count(),
        ]);
    }
}

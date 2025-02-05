<?php

namespace App\Http\Controllers;

use App\Actions\Messages\OpenConversation;
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
                    $query->latest()->take(1);
                }
            ])
            ->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')
            ->whereHas('messages')
            ->get();

        $conversations = $results->map(function ($conversation) {
            return [
                'id' => $conversation->id,
                'user_id' => $conversation->users->first()->id,
                'name' => $conversation->users->first()->name,
                'avatar' => $conversation->users->first()->avatar,
                'latest_message' => $conversation->messages,
                'unread_messages_count' => $conversation->messages->where('is_read', false)->where('sender_id', '!=', auth()->id())->count(),
            ];
        });

        return Inertia::render('Messages/Index', compact('conversations'));
    }

    public function openConversation(Request $request, $user_id, OpenConversation $openConversation)
    {
        $authUserId = auth()->id();
        $user = User::find($user_id);

        $results = $openConversation->handle($user_id, $authUserId);
        $conversation = $results['conversation'];
        $messages = $results['messages'];

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
        try {
            $request->validate([
                'message' => 'required',
                'files' => 'nullable|array',
                'files.*' => [
                    'file',
                    'mimetypes:image/jpeg,image/png,image/gif,video/mp4,
                        video/quicktime,video/mpeg,video/ogg,video/webm,video/avi,application/pdf',
                    'max:10240' // 10MB
                ],
            ]);
            $conversation = Conversation::query()->find($conversation_id);
            Gate::authorize('send', $conversation);

            $message = $conversation->messages()->create([
                'sender_id' => auth()->id(),
                'content' => $request->message,
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $message->addMedia($file)
                        ->toMediaCollection('message_media');
                }
            }

            $message->load('sender', 'media');

//            broadcast(new MessageSent($message));

            return response()->json([
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'content' => $message->content,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name,
                'media' => array_values($message->getMedia('message_media')->toArray())
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ]);
        }

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

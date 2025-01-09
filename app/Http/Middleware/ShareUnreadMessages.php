<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Conversation;

class ShareUnreadMessages
{
    public function handle(Request $request, Closure $next)
    {
        // Only share unread messages for authenticated users
        if (auth()->check()) {
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

            Inertia::share('unreadCount', [
                'conversations' => $unreadCount,
                'total' => array_sum(array_column($unreadCount->toArray(), 'messages_count')),
            ]);
        }

        return $next($request);
    }
}
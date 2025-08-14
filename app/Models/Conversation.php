<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_users', 'conversation_id', 'user_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Count unread messages for a specific user
     * Excludes messages sent by the user themselves
     */
    public function unreadMessagesCount($userId): int
    {
        return $this->messages()
            ->where('is_read', false)
            ->where('sender_id', '!=', $userId)
            ->count();
    }

    /**
     * Get unread messages for a specific user
     * Excludes messages sent by the user themselves
     */
    public function unreadMessages($userId)
    {
        return $this->messages()
            ->where('is_read', false)
            ->where('sender_id', '!=', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function otherUser($currentUserId)
    {
        return $this->users()->where('users.id', '!=', $currentUserId);
    }
}

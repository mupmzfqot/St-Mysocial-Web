<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBlock;
use App\Models\Conversation;
use App\Models\ConversationUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BlockService
{
    /**
     * Block a user - soft delete conversations only
     * Messages remain in database
     * Posts and comments remain in database but are filtered in queries
     */
    public function blockUser(User $blocker, User $blocked): bool
    {
        // Prevent self-blocking
        if ($blocker->id === $blocked->id) {
            return false;
        }

        // Check if already blocked
        if ($blocker->hasBlocked($blocked->id)) {
            return true; // Already blocked, return success
        }

        DB::beginTransaction();
        try {
            // Create block record
            UserBlock::create([
                'blocker_id' => $blocker->id,
                'blocked_id' => $blocked->id,
            ]);

            // Note: Posts and comments are NOT deleted
            // They remain in database but are filtered in queries using getBlockedUserIds()
            // This ensures other users can still see the content

            // Soft delete conversations between both users
            // Messages remain in database
            $this->softDeleteConversationsBetweenUsers($blocker, $blocked);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to block user', [
                'blocker_id' => $blocker->id,
                'blocked_id' => $blocked->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Unblock a user
     * Restore soft-deleted conversations
     */
    public function unblockUser(User $blocker, User $blocked): bool
    {
        DB::beginTransaction();
        try {
            // Delete block record
            UserBlock::where('blocker_id', $blocker->id)
                ->where('blocked_id', $blocked->id)
                ->delete();

            // Note: Posts and comments were never deleted
            // They will automatically become visible again after unblock
            // because queries filter based on block status

            // Restore soft-deleted conversations between both users
            $this->restoreConversationsBetweenUsers($blocker, $blocked);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to unblock user', [
                'blocker_id' => $blocker->id,
                'blocked_id' => $blocked->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Soft delete conversations between two users
     * Messages remain in database
     */
    protected function softDeleteConversationsBetweenUsers(User $user1, User $user2): void
    {
        // Find conversations where both users are members
        $conversationIds = ConversationUser::whereIn('user_id', [$user1->id, $user2->id])
            ->groupBy('conversation_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 2')
            ->pluck('conversation_id');

        foreach ($conversationIds as $conversationId) {
            // Check if conversation only has these 2 users
            $conversation = Conversation::withTrashed()->find($conversationId);
            if ($conversation && $conversation->users()->count() === 2) {
                // Soft delete conversation (messages remain in database)
                if (!$conversation->trashed()) {
                    $conversation->delete();
                }
            }
        }
    }

    /**
     * Restore soft-deleted conversations between two users
     */
    protected function restoreConversationsBetweenUsers(User $user1, User $user2): void
    {
        // Find soft-deleted conversations where both users are members
        $conversationIds = ConversationUser::whereIn('user_id', [$user1->id, $user2->id])
            ->groupBy('conversation_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 2')
            ->pluck('conversation_id');

        foreach ($conversationIds as $conversationId) {
            // Check if conversation only has these 2 users
            $conversation = Conversation::withTrashed()->find($conversationId);
            if ($conversation && $conversation->users()->count() === 2) {
                // Restore soft-deleted conversation
                if ($conversation->trashed()) {
                    $conversation->restore();
                }
            }
        }
    }

    /**
     * Get blocked user IDs for a user (bidirectional)
     */
    public function getBlockedUserIds(User $user): array
    {
        $blockedIds = UserBlock::where('blocker_id', $user->id)
            ->pluck('blocked_id')
            ->toArray();

        $blockedByIds = UserBlock::where('blocked_id', $user->id)
            ->pluck('blocker_id')
            ->toArray();

        return array_unique(array_merge($blockedIds, $blockedByIds));
    }
}


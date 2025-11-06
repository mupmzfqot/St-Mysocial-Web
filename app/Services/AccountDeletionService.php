<?php

namespace App\Services;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\ConversationUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountDeletionService
{
    /**
     * Request account deletion - soft delete all related data
     */
    public function requestDeletion(User $user, ?string $reason = null): bool
    {
        DB::beginTransaction();
        try {
            // Update user status
            $user->account_status = 'deletion_requested';
            $user->deletion_requested_at = now();
            $user->scheduled_deletion_at = now()->addDays(30);
            $user->deletion_reason = $reason;
            $user->save();

            // Soft delete all related data
            $this->softDeleteUserData($user);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to request account deletion', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reactivate account - restore all soft deleted data
     */
    public function reactivateAccount(User $user, bool $restoreData = true): bool
    {
        DB::beginTransaction();
        try {
            // Reactivate account
            $user->account_status = 'active';
            $user->deletion_requested_at = null;
            $user->scheduled_deletion_at = null;
            $user->deletion_reason = null;
            $user->save();

            // Restore all soft deleted data if requested
            if ($restoreData) {
                $this->restoreUserData($user);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reactivate account', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Soft delete all user data
     */
    protected function softDeleteUserData(User $user): void
    {
        // Soft delete posts
        Post::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->chunk(100, function ($posts) {
                foreach ($posts as $post) {
                    $post->delete(); // Soft delete
                }
            });

        // Soft delete comments
        Comment::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->chunk(100, function ($comments) {
                foreach ($comments as $comment) {
                    $comment->delete(); // Soft delete
                }
            });

        // Soft delete conversations
        $conversationIds = ConversationUser::where('user_id', $user->id)
            ->pluck('conversation_id');
        
        Conversation::whereIn('id', $conversationIds)
            ->whereNull('deleted_at')
            ->chunk(100, function ($conversations) {
                foreach ($conversations as $conversation) {
                    $conversation->delete(); // Soft delete
                }
            });
    }

    /**
     * Restore all soft deleted user data
     */
    protected function restoreUserData(User $user): void
    {
        // Restore posts
        Post::where('user_id', $user->id)
            ->onlyTrashed()
            ->chunk(100, function ($posts) {
                foreach ($posts as $post) {
                    $post->restore();
                }
            });

        // Restore comments
        Comment::where('user_id', $user->id)
            ->onlyTrashed()
            ->chunk(100, function ($comments) {
                foreach ($comments as $comment) {
                    $comment->restore();
                }
            });

        // Restore conversations
        $conversationIds = ConversationUser::where('user_id', $user->id)
            ->pluck('conversation_id');
        
        Conversation::whereIn('id', $conversationIds)
            ->onlyTrashed()
            ->chunk(100, function ($conversations) {
                foreach ($conversations as $conversation) {
                    $conversation->restore();
                }
            });
    }

    /**
     * Process permanent deletions for users whose grace period has expired
     */
    public function processPermanentDeletions(): int
    {
        $usersToDelete = User::where('account_status', 'deletion_requested')
            ->where('scheduled_deletion_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($usersToDelete as $user) {
            DB::beginTransaction();
            try {
                // Soft delete user (final)
                $user->account_status = 'deleted';
                $user->deleted_at = now();
                $user->save();

                DB::commit();
                $count++;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to permanently delete user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }
}


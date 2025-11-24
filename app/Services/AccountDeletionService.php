<?php

namespace App\Services;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Message;
use App\Models\PostLiked;
use App\Models\PostTag;
use App\Models\UserBlock;
use App\Models\Report;
use App\Models\UserDevice;
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
            $user->deleted_at = null;
            $user->deletion_requested_at = null;
            $user->scheduled_deletion_at = null;
            // $user->deletion_reason = null;
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

    /**
     * Permanently delete a user and all related data (admin only)
     * This will force delete all related records and cannot be undone
     */
    public function permanentDeleteUser(User $user): bool
    {
        DB::beginTransaction();
        try {
            // Delete media files
            $user->clearMediaCollection('avatar');
            $user->clearMediaCollection('cover_image');

            // Force delete posts (with media)
            Post::where('user_id', $user->id)
                ->withTrashed()
                ->chunk(100, function ($posts) {
                    foreach ($posts as $post) {
                        // Delete post media
                        $post->clearMediaCollection('media');
                        // Force delete post
                        $post->forceDelete();
                    }
                });

            // Force delete comments (with media)
            Comment::where('user_id', $user->id)
                ->withTrashed()
                ->chunk(100, function ($comments) {
                    foreach ($comments as $comment) {
                        // Delete comment media
                        $comment->clearMediaCollection('media');
                        // Force delete comment
                        $comment->forceDelete();
                    }
                });

            // Delete messages
            Message::where('sender_id', $user->id)->delete();

            // Get conversation IDs before deleting relationships
            $conversationIds = ConversationUser::where('user_id', $user->id)
                ->pluck('conversation_id')
                ->unique();
            
            // Delete conversation relationships
            ConversationUser::where('user_id', $user->id)->delete();

            // Check and delete empty conversations
            foreach ($conversationIds as $conversationId) {
                $participantCount = ConversationUser::where('conversation_id', $conversationId)->count();
                if ($participantCount === 0) {
                    // Delete messages in conversation first
                    \App\Models\Message::where('conversation_id', $conversationId)->delete();
                    // Then delete the conversation
                    Conversation::where('id', $conversationId)->withTrashed()->forceDelete();
                }
            }

            // Delete likes
            PostLiked::where('user_id', $user->id)->delete();

            // Delete tags
            PostTag::where('user_id', $user->id)->delete();

            // Delete user blocks (both directions)
            UserBlock::where('blocker_id', $user->id)->delete();
            UserBlock::where('blocked_id', $user->id)->delete();

            // Delete reports (both as reporter and reported)
            Report::where('reporter_id', $user->id)->delete();
            Report::where('reportable_type', User::class)
                ->where('reportable_id', $user->id)
                ->delete();

            // Delete devices
            UserDevice::where('user_id', $user->id)->delete();

            // Delete notifications
            $user->notifications()->delete();

            // Delete tokens
            $user->tokens()->delete();

            // Finally, force delete the user
            $user->forceDelete();

            DB::commit();
            
            Log::info('User permanently deleted', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to permanently delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}


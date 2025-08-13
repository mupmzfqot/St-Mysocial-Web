<?php

namespace App\Observers;

use App\Models\Comment;
use App\Services\PostCacheService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class CommentObserver
{
    protected $postCacheService;

    public function __construct()
    {
        $this->postCacheService = App::make(PostCacheService::class);
    }

    public function created(Comment $comment): void
    {
        Log::info("CommentObserver: Comment created", [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id
        ]);
        $this->postCacheService->invalidateCommentCache($comment->id);
    }

    public function deleted(Comment $comment): void
    {
        Log::info("CommentObserver: Comment deleted", [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id
        ]);
        $this->postCacheService->invalidateCommentCache($comment->id);
    }
} 
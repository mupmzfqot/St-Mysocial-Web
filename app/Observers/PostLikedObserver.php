<?php

namespace App\Observers;

use App\Models\PostLiked;
use App\Services\PostCacheService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class PostLikedObserver
{
    protected $postCacheService;

    public function __construct()
    {
        $this->postCacheService = App::make(PostCacheService::class);
    }

    public function created(PostLiked $like): void
    {
        Log::info("PostLikedObserver: Like created", [
            'like_id' => $like->id,
            'post_id' => $like->post_id
        ]);
        $this->postCacheService->invalidateLikeCache($like->id);
    }

    public function deleted(PostLiked $like): void
    {
        Log::info("PostLikedObserver: Like deleted", [
            'like_id' => $like->id,
            'post_id' => $like->post_id
        ]);
        $this->postCacheService->invalidateLikeCache($like->id);
    }
} 
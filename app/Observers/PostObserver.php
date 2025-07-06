<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\PostCacheService;
use Illuminate\Support\Facades\Log;

class PostObserver
{
    protected $postCacheService;
    
    public function __construct(PostCacheService $postCacheService)
    {
        $this->postCacheService = $postCacheService;
    }

    public function created(Post $post): void
    {
        try {
            $this->clearCacheByType($post->type);
        } catch (\Exception $e) {
            Log::error("Failed to clear cache after post created", ['error' => $e->getMessage()]);
        }
    }

    public function updated(Post $post): void
    {
        try {
            $this->clearCacheByType($post->type);
        } catch (\Exception $e) {
            Log::error("Failed to clear cache after post updated", ['error' => $e->getMessage()]);
        }
    }

    public function deleted(Post $post): void
    {
        try {
            $this->clearCacheByType($post->type);
        } catch (\Exception $e) {
            Log::error("Failed to clear cache after post deleted", ['error' => $e->getMessage()]);
        }
    }

    private function clearCacheByType(?string $type): void
    {
        if ($type) {
            $this->postCacheService->clearCache($type);
        } else {
            $this->postCacheService->clearCache();
        }
    }
} 
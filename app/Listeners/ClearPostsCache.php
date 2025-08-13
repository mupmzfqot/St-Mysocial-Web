<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use App\Events\PostCreated;

class ClearPostsCache
{
    public function handle(PostCreated $event): void
    {
        // Clear semua cache posts
        Cache::tags(['posts'])->flush();
        
        // Atau clear berdasarkan type post
        if ($event->post->type) {
            Cache::tags(["posts_{$event->post->type}"])->flush();
        }
    }
} 
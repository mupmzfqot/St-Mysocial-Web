<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PostCacheService;

class ClearPostsCache extends Command
{
    protected $signature = 'posts:clear-cache {type?}';
    protected $description = 'Clear posts cache for specific type or all types';

    public function handle(PostCacheService $postCacheService)
    {
        $type = $this->argument('type');
        
        if ($type) {
            $postCacheService->clearCache($type);
            $this->info("Cache cleared for posts type: {$type}");
        } else {
            $postCacheService->clearCache();
            $this->info("All posts cache cleared");
        }
    }
} 
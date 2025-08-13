<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PostCacheService;

class PostsCacheStats extends Command
{
    protected $signature = 'posts:cache-stats';
    protected $description = 'Show posts cache statistics';

    public function handle(PostCacheService $postCacheService)
    {
        $stats = $postCacheService->getCacheStats();
        
        $this->table(
            ['Type', 'Signature', 'Cache Keys'],
            collect($stats)->map(function ($stat, $type) {
                return [
                    $type,
                    $stat['signature'] ?? 'N/A',
                    count($stat['cache_keys'])
                ];
            })->toArray()
        );
    }
} 
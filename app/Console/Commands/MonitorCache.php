<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\PostCacheService;
use Predis\Client;

class MonitorCache extends Command
{
    protected $signature = 'cache:monitor {--clear : Clear all cache} {--stats : Show cache statistics}';
    protected $description = 'Monitor Redis cache status and performance';

    public function handle()
    {
        $this->info('=== Redis Cache Monitor ===');

        // Check Redis connection
        try {
            $redis = new Client([
                'host' => config('database.redis.cache.host'),
                'port' => config('database.redis.cache.port'),
                'database' => config('database.redis.cache.database'),
            ]);
            
            $this->info('✅ Redis connection: OK');
            $this->info('📊 Database ' . config('database.redis.cache.database') . ' keys: ' . $redis->dbsize());
            
            // Get all keys
            $keys = $redis->keys('*');
            $this->info('🔑 Total keys: ' . count($keys));
            
            // Show posts cache keys
            $postsKeys = $redis->keys('*posts*');
            $this->info('📝 Posts cache keys (' . count($postsKeys) . '):');
            foreach ($postsKeys as $key) {
                $ttl = $redis->ttl($key);
                $type = $ttl > 0 ? 'TTL: ' . $ttl . 's' : 'No TTL';
                $this->line("  - $key ($type)");
            }
            
            // Test cache service
            $this->info("\n=== Cache Test ===");
            $cacheService = app(PostCacheService::class);
            
            $types = ['public', 'private', 'team'];
            foreach ($types as $type) {
                $this->info("Testing cache for type: $type");
                try {
                    $posts = $cacheService->getCachedPosts($type, null, 20, '');
                    $this->info("  ✅ Cached successfully. Total posts: " . $posts->total());
                } catch (\Exception $e) {
                    $this->error("  ❌ Error: " . $e->getMessage());
                }
            }
            
            // Show cache stats
            if ($this->option('stats')) {
                $this->info("\n=== Cache Stats ===");
                $stats = $cacheService->getCacheStats();
                foreach ($stats as $type => $stat) {
                    $this->info("Type: $type");
                    $this->line("  Signature: " . ($stat['signature'] ?? 'N/A'));
                    $this->line("  Cache keys: " . count($stat['cache_keys']));
                }
            }
            
            // Clear cache if requested
            if ($this->option('clear')) {
                $this->info("\n=== Clearing Cache ===");
                $redis->flushdb();
                $this->info("✅ All cache cleared");
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Redis connection failed: ' . $e->getMessage());
        }

        $this->info("\n=== End Monitor ===");
    }
} 
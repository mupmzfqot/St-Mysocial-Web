<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Cache;
use App\Services\PostCacheService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Redis Cache Monitor ===\n\n";

// Check Redis connection
try {
    $redis = new \Predis\Client([
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 1,
    ]);
    
    echo "✅ Redis connection: OK\n";
    echo "📊 Database 1 keys: " . $redis->dbsize() . "\n";
    
    // Get all keys
    $keys = $redis->keys('*');
    echo "🔑 Total keys: " . count($keys) . "\n\n";
    
    // Show posts cache keys
    $postsKeys = $redis->keys('*posts*');
    echo "📝 Posts cache keys (" . count($postsKeys) . "):\n";
    foreach ($postsKeys as $key) {
        $ttl = $redis->ttl($key);
        $type = $ttl > 0 ? 'TTL: ' . $ttl . 's' : 'No TTL';
        echo "  - $key ($type)\n";
    }
    
    echo "\n=== Cache Test ===\n";
    
    // Test cache service
    $cacheService = app(PostCacheService::class);
    
    // Test different post types
    $types = ['public', 'private', 'team'];
    foreach ($types as $type) {
        echo "Testing cache for type: $type\n";
        try {
            $posts = $cacheService->getCachedPosts($type, null, 20, '');
            echo "  ✅ Cached successfully. Total posts: " . $posts->total() . "\n";
        } catch (Exception $e) {
            echo "  ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Cache Stats ===\n";
    $stats = $cacheService->getCacheStats();
    foreach ($stats as $type => $stat) {
        echo "Type: $type\n";
        echo "  Signature: " . ($stat['signature'] ?? 'N/A') . "\n";
        echo "  Cache keys: " . count($stat['cache_keys']) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Redis connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== End Monitor ===\n"; 
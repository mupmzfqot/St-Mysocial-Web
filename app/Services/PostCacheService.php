<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class PostCacheService
{
    private const CACHE_TTL = 3600; // 1 hour (lebih lama)
    private const SIGNATURE_TTL = 86400; // 24 hours
    private const POST_TYPES = ['public', 'private', 'team', 'st'];
    
    public function getCachedPosts(string $type, ?string $searchTerm, int $perPage, string $queryString): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey($type, $searchTerm, $perPage, $queryString);
        Log::info("Cache key generated", [
            'type' => $type,
            'searchTerm' => $searchTerm,
            'perPage' => $perPage,
            'queryString' => $queryString,
            'cacheKey' => $cacheKey
        ]);
        
        try {
            if (Cache::has($cacheKey)) {
                Log::info("Cache HIT", ['cacheKey' => $cacheKey]);
                return Cache::get($cacheKey);
            }
            
            Log::info("Cache MISS - building query", ['cacheKey' => $cacheKey]);
            $result = $this->buildQuery($type, $searchTerm, $perPage);
            Cache::put($cacheKey, $result, self::CACHE_TTL);
            Log::info("Cache stored", ['cacheKey' => $cacheKey, 'ttl' => self::CACHE_TTL]);
            return $result;
        } catch (\Exception $e) {
            Log::error("Cache error for posts type: {$type}", ['error' => $e->getMessage()]);
            return $this->buildQuery($type, $searchTerm, $perPage);
        }
    }
    
    private function buildQuery(string $type, ?string $searchTerm, int $perPage): LengthAwarePaginator
    {
        return Post::query()
            ->when($searchTerm, function ($query, $search) {
                $query->where('post', 'like', '%' . $search . '%');
            })
            ->where('type', $type)
            ->published()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->with(['author', 'media', 'repost'])
            ->paginate($perPage)
            ->withQueryString();
    }
    
    public function clearCache(?string $type = null): void
    {
        try {
            if ($type) {
                $this->clearCacheByType($type);
            } else {
                foreach (self::POST_TYPES as $type) {
                    $this->clearCacheByType($type);
                }
            }
        } catch (\Exception $e) {
            Log::error("Cache clear error", ['error' => $e->getMessage()]);
        }
    }
    
    private function generateCacheKey(string $type, ?string $searchTerm, int $perPage, string $queryString): string
    {
        $signature = $this->getCacheSignature($type);
        $hash = md5($searchTerm . $perPage . $queryString);
        return "posts_{$type}_s{$signature}_{$hash}";
    }
    
    private function getCacheSignature(string $type): string
    {
        $signatureKey = "signature_{$type}";
        if (!Cache::has($signatureKey)) {
            $newSignature = md5(time() . rand(1000, 9999));
            Cache::put($signatureKey, $newSignature, self::SIGNATURE_TTL);
        }
        return Cache::get($signatureKey);
    }
    
    private function updateCacheSignature(string $type): void
    {
        $newSignature = md5(time() . rand(1000, 9999));
        Cache::put("signature_{$type}", $newSignature, self::SIGNATURE_TTL);
    }
    
    private function updateAllCacheSignatures(): void
    {
        foreach (self::POST_TYPES as $type) {
            $this->updateCacheSignature($type);
        }
    }
    
    private function clearCacheByType(string $type): void
    {
        // Delete all keys for this type with any signature
        $pattern = "posts_{$type}_s*";
        $this->clearCacheByPattern($pattern);
        // Now update the signature so new cache keys will be used
        $this->updateCacheSignature($type);
    }

    private function clearAllCache(): void
    {
        $pattern = "posts_*";
        $this->clearCacheByPattern($pattern);
    }

    private function clearCacheByPattern(string $pattern): void
    {
        if (config('cache.default') === 'redis') {
            try {
                // Use Redis database 1 for cache
                $redis = Cache::getRedis();
                $redis->select(1); // Switch to database 1
                
                // Add the correct prefix to the pattern
                $prefix = config('database.redis.cache.prefix', 'teamst_database_');
                $fullPattern = $prefix . $pattern;
                
                Log::info("Clearing cache by pattern", [
                    'pattern' => $pattern,
                    'full_pattern' => $fullPattern,
                    'prefix' => $prefix,
                    'redis_db' => 1
                ]);
                
                $keys = $redis->keys($fullPattern);
                Log::info("Cache keys found", [
                    'keys_found' => count($keys),
                    'keys' => $keys
                ]);
                
                if (!empty($keys)) {
                    $deletedCount = $redis->del($keys);
                    Log::info("Cache keys deleted", [
                        'deleted_keys' => $keys,
                        'deleted_count' => $deletedCount
                    ]);
                } else {
                    Log::info("No cache keys found for pattern", ['pattern' => $fullPattern]);
                }
            } catch (\Exception $e) {
                Log::error("Redis cache clearing error", [
                    'error' => $e->getMessage(),
                    'pattern' => $pattern
                ]);
            }
        } else {
            Cache::flush();
        }
    }
    
    // Helper method untuk monitoring cache
    public function getCacheStats(): array
    {
        $stats = [];
        $types = ['public', 'private', 'team'];
        
        foreach ($types as $type) {
            $signature = Cache::tags(['posts'])->get("signature_{$type}");
            $stats[$type] = [
                'signature' => $signature,
                'cache_keys' => $this->getCacheKeysForType($type)
            ];
        }
        
        return $stats;
    }
    
    private function getCacheKeysForType(string $type): array
    {
        // Note: This is a simplified version. In production, you might want to use Redis SCAN
        return [];
    }

    public function invalidatePostCache(int $postId): void
    {
        try {
            $post = Post::find($postId);
            if ($post) {
                Log::info("Invalidating cache for post ID: {$postId}, type: {$post->type}");
                $this->clearCacheByType($post->type);
            } else {
                Log::warning("Post not found for cache invalidation: {$postId}");
            }
        } catch (\Exception $e) {
            Log::error("Cache invalidation error for post ID: {$postId}", ['error' => $e->getMessage()]);
        }
    }

    public function invalidateCommentCache(int $commentId): void
    {
        try {
            $comment = \App\Models\Comment::find($commentId);
            if ($comment) {
                Log::info("Invalidating cache for comment ID: {$commentId}, post ID: {$comment->post_id}");
                $this->invalidatePostCache($comment->post_id);
            } else {
                Log::warning("Comment not found for cache invalidation: {$commentId}");
            }
        } catch (\Exception $e) {
            Log::error("Cache invalidation error for comment ID: {$commentId}", ['error' => $e->getMessage()]);
        }
    }

    public function invalidateLikeCache(int $likeId): void
    {
        try {
            $like = \App\Models\PostLiked::find($likeId);
            if ($like) {
                Log::info("Invalidating cache for like ID: {$likeId}, post ID: {$like->post_id}");
                $this->invalidatePostCache($like->post_id);
            } else {
                Log::warning("Like not found for cache invalidation: {$likeId}");
            }
        } catch (\Exception $e) {
            Log::error("Cache invalidation error for like ID: {$likeId}", ['error' => $e->getMessage()]);
        }
    }

    public function warmCache(): void
    {
        $perPages = [10, 20, 50];
        foreach (self::POST_TYPES as $type) {
            foreach ($perPages as $perPage) {
                try {
                    $this->getCachedPosts($type, '', $perPage, '');
                } catch (\Exception $e) {
                    Log::error("Cache warming failed for type: {$type}", ['error' => $e->getMessage()]);
                }
            }
        }
    }
} 
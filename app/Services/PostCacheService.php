<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class PostCacheService
{
    private const CACHE_TTL = 900; // 15 minutes
    private const SIGNATURE_TTL = 86400; // 24 hours
    
    public function getCachedPosts(string $type, ?string $searchTerm, int $perPage, string $queryString): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey($type, $searchTerm, $perPage, $queryString);
        
        try {
            // Check if Redis is available
            if (config('cache.default') === 'redis') {
                return Cache::tags(['posts', "posts_{$type}"])->remember($cacheKey, self::CACHE_TTL, function () use ($type, $searchTerm, $perPage) {
                    Log::info("Cache miss for posts type: {$type}");
                    return $this->buildQuery($type, $searchTerm, $perPage);
                });
            } else {
                // Fallback to simple cache without tags
                return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type, $searchTerm, $perPage) {
                    Log::info("Cache miss for posts type: {$type}");
                    return $this->buildQuery($type, $searchTerm, $perPage);
                });
            }
        } catch (\Exception $e) {
            Log::error("Cache error for posts type: {$type}", ['error' => $e->getMessage()]);
            // Fallback to direct query if cache fails
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
            ->with(['author', 'media'])
            ->paginate($perPage)
            ->withQueryString();
    }
    
    public function clearCache(?string $type = null): void
    {
        try {
            if ($type) {
                $this->updateCacheSignature($type);
                Cache::tags(["posts_{$type}"])->flush();
                Log::info("Cache cleared for posts type: {$type}");
            } else {
                $this->updateAllCacheSignatures();
                Cache::tags(['posts'])->flush();
                Log::info("All posts cache cleared");
            }
        } catch (\Exception $e) {
            Log::error("Cache clear error", ['error' => $e->getMessage()]);
        }
    }
    
    private function generateCacheKey(string $type, ?string $searchTerm, int $perPage, string $queryString): string
    {
        $signature = $this->getCacheSignature($type);
        return "posts_{$type}_s{$signature}_" . md5($searchTerm . $perPage . $queryString);
    }
    
    private function getCacheSignature(string $type): string
    {
        return Cache::tags(['posts'])->remember("signature_{$type}", self::SIGNATURE_TTL, function () {
            return md5(time() . rand(1000, 9999));
        });
    }
    
    private function updateCacheSignature(string $type): void
    {
        $newSignature = md5(time() . rand(1000, 9999));
        Cache::tags(['posts'])->put("signature_{$type}", $newSignature, self::SIGNATURE_TTL);
    }
    
    private function updateAllCacheSignatures(): void
    {
        $types = ['public', 'private', 'team'];
        foreach ($types as $type) {
            $this->updateCacheSignature($type);
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
} 
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Services\VideoStreamingService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class VideoStreamingMiddleware
{
    protected VideoStreamingService $videoStreamingService;

    public function __construct(VideoStreamingService $videoStreamingService)
    {
        $this->videoStreamingService = $videoStreamingService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        // Debug logging
        \Log::info('VideoStreamingMiddleware: Request path: ' . $request->path());
        
        // Check if this is a storage file request
        if (!$this->isStorageRequest($request)) {
            \Log::info('VideoStreamingMiddleware: Not a storage request');
            return $next($request);
        }

        \Log::info('VideoStreamingMiddleware: Storage request detected');

        // Try to find the media by URL
        $media = $this->findMediaByUrl($request);
        
        if (!$media) {
            \Log::info('VideoStreamingMiddleware: Media not found');
            return $next($request);
        }

        \Log::info('VideoStreamingMiddleware: Media found', ['id' => $media->id, 'name' => $media->name]);

        // Check if it's a video file
        if (!$this->videoStreamingService->isVideo($media)) {
            \Log::info('VideoStreamingMiddleware: Not a video file');
            return $next($request);
        }

        \Log::info('VideoStreamingMiddleware: Video file detected, starting stream');

        // For video files, handle streaming
        return $this->videoStreamingService->streamVideo($media, $request);
    }

    /**
     * Check if the request is for a media file
     */
    private function isMediaRequest(Request $request): bool
    {
        $path = $request->path();
        
        // Check if path contains storage/media or similar media library paths
        return str_contains($path, 'storage') && 
               (str_contains($path, 'media') || 
                str_contains($path, 'post') || 
                str_contains($path, 'photo') || 
                str_contains($path, 'video') ||
                str_contains($path, 'gallery'));
    }

    /**
     * Check if the request is for a storage file
     */
    private function isStorageRequest(Request $request): bool
    {
        $path = $request->path();
        
        // Don't intercept our custom streaming route
        if (str_starts_with($path, 'stream-video/')) {
            return false;
        }
        
        return str_starts_with($path, 'storage/');
    }

    /**
     * Find media by URL path
     */
    private function findMediaByUrl(Request $request): ?Media
    {
        $path = $request->path();
        
        // Extract filename from path
        $filename = basename($path);
        
        // Remove query parameters
        $filename = explode('?', $filename)[0];
        
        // Try to find media by filename
        $media = Media::where('file_name', $filename)->first();
        
        if ($media) {
            return $media;
        }

        // Try to find by name (without extension)
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        $media = Media::where('name', $nameWithoutExt)->first();
        
        if ($media) {
            return $media;
        }

        // Try to find by collection name and filename
        $pathParts = explode('/', $path);
        if (count($pathParts) >= 3) {
            $collectionName = $pathParts[count($pathParts) - 2];
            $media = Media::where('collection_name', $collectionName)
                         ->where('file_name', $filename)
                         ->first();
            
            if ($media) {
                return $media;
            }
        }

        return null;
    }

    /**
     * Get media info for debugging (optional)
     */
    public function getMediaInfo(Request $request): array
    {
        $media = $this->findMediaByUrl($request);
        
        if (!$media) {
            return ['error' => 'Media not found'];
        }

        return $this->videoStreamingService->getVideoInfo($media);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Services\VideoStreamingService;
use App\Http\Middleware\VideoStreamingMiddleware;

class VideoStreamingController extends Controller
{
    protected VideoStreamingService $videoStreamingService;
    protected VideoStreamingMiddleware $videoStreamingMiddleware;

    public function __construct(
        VideoStreamingService $videoStreamingService,
        VideoStreamingMiddleware $videoStreamingMiddleware
    ) {
        $this->videoStreamingService = $videoStreamingService;
        $this->videoStreamingMiddleware = $videoStreamingMiddleware;
    }

    /**
     * Get video info for debugging
     */
    public function getVideoInfo(Request $request)
    {
        $mediaId = $request->get('media_id');
        $url = $request->get('url');

        if ($mediaId) {
            $media = Media::find($mediaId);
            if (!$media) {
                return response()->json(['error' => 'Media not found'], 404);
            }
            
            return response()->json([
                'media_info' => $this->videoStreamingService->getVideoInfo($media),
                'is_video' => $this->videoStreamingService->isVideo($media)
            ]);
        }

        if ($url) {
            // Create a mock request to test URL parsing
            $mockRequest = Request::create($url);
            $mediaInfo = $this->videoStreamingMiddleware->getMediaInfo($mockRequest);
            
            return response()->json([
                'url' => $url,
                'media_info' => $mediaInfo
            ]);
        }

        return response()->json(['error' => 'Please provide media_id or url parameter'], 400);
    }

    /**
     * List all video files in media library
     */
    public function listVideos()
    {
        $videos = Media::where('mime_type', 'like', 'video/%')
                      ->with(['model'])
                      ->get()
                      ->map(function ($media) {
                          return [
                              'id' => $media->id,
                              'name' => $media->name,
                              'file_name' => $media->file_name,
                              'mime_type' => $media->mime_type,
                              'size' => $media->size,
                              'collection_name' => $media->collection_name,
                              'model_type' => $media->model_type,
                              'model_id' => $media->model_id,
                              'url' => $media->getUrl(),
                              'stream_url' => url("/media/{$media->id}/stream")
                          ];
                      });

        return response()->json([
            'total_videos' => $videos->count(),
            'videos' => $videos
        ]);
    }

    /**
     * Test streaming for a specific media
     */
    public function testStream(Request $request)
    {
        $mediaId = $request->get('media_id');
        
        if (!$mediaId) {
            return response()->json(['error' => 'Media ID required'], 400);
        }

        $media = Media::find($mediaId);
        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        if (!$this->videoStreamingService->isVideo($media)) {
            return response()->json(['error' => 'Media is not a video file'], 400);
        }

        // Return streaming info without actually streaming
        return response()->json([
            'media_id' => $media->id,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'streaming_ready' => true,
            'test_url' => url("/media/{$media->id}/stream"),
            'headers_example' => [
                'Accept-Ranges' => 'bytes',
                'Content-Type' => $media->mime_type,
                'Cache-Control' => 'no-cache'
            ]
        ]);
    }

    /**
     * Stream video by filename (public access)
     */
    public function streamVideoByFilename($filename)
    {
        \Log::info('VideoStreamingController: streamVideoByFilename called', ['filename' => $filename]);
        
        // Find media by filename
        $media = Media::where('file_name', $filename)->first();
        
        if (!$media) {
            \Log::warning('VideoStreamingController: Media not found', ['filename' => $filename]);
            abort(404, 'Video not found');
        }
        
        \Log::info('VideoStreamingController: Media found', [
            'id' => $media->id,
            'filename' => $media->file_name,
            'mime_type' => $media->mime_type
        ]);
        
        if (!$this->videoStreamingService->isVideo($media)) {
            \Log::warning('VideoStreamingController: Not a video file', [
                'filename' => $filename,
                'mime_type' => $media->mime_type
            ]);
            abort(400, 'Not a video file');
        }
        
        \Log::info('VideoStreamingController: Starting video stream');
        
        try {
            return $this->videoStreamingService->streamVideo($media, request());
        } catch (\Exception $e) {
            \Log::error('VideoStreamingController: Error streaming video', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

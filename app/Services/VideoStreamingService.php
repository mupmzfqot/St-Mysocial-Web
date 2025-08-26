<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStreamingService
{
    /**
     * Stream video file with proper headers for iOS AVPlayer
     */
    public function streamVideo(Media $media, Request $request): StreamedResponse
    {
        // Try to find the file in the correct disk
        $disk = null;
        $path = null;
        
        // First try the media's configured disk
        if (Storage::disk($media->disk)->exists($media->getPath())) {
            $disk = Storage::disk($media->disk);
            $path = $media->getPath();
        }
        // Fallback to public disk with relative path
        elseif (Storage::disk('public')->exists('media/' . $media->id . '/' . $media->file_name)) {
            $disk = Storage::disk('public');
            $path = 'media/' . $media->id . '/' . $media->file_name;
        }
        // Last fallback to public disk with media collection path
        elseif (Storage::disk('public')->exists('media/' . $media->file_name)) {
            $disk = Storage::disk('public');
            $path = 'media/' . $media->file_name;
        }
        else {
            abort(404, 'Video file not found');
        }

        $fileSize = $disk->size($path);
        $mimeType = $media->mime_type;
        
        // Debug logging
        \Log::info('VideoStreamingService: Starting stream', [
            'filename' => $media->file_name,
            'fileSize' => $fileSize,
            'mimeType' => $mimeType,
            'disk_name' => $media->disk,
            'path' => $path,
            'range_header' => $request->header('Range'),
            'user_agent' => $request->header('User-Agent')
        ]);
        
        // Parse range header
        $range = $this->parseRangeHeader($request, $fileSize);
        
        \Log::info('VideoStreamingService: Range parsed', $range);
        
        // Set response headers
        $headers = $this->getStreamingHeaders($mimeType, $fileSize, $range);
        
        \Log::info('VideoStreamingService: Headers generated', $headers);
        
        // Create streamed response
        return $this->createStreamedResponse($disk, $path, $range, $headers);
    }

    /**
     * Parse Range header from request
     */
    private function parseRangeHeader(Request $request, int $fileSize): array
    {
        $rangeHeader = $request->header('Range');
        
        \Log::info('VideoStreamingService: Raw range header', ['range' => $rangeHeader]);
        
        if (!$rangeHeader) {
            \Log::info('VideoStreamingService: No range header found');
            return [
                'start' => 0,
                'end' => $fileSize - 1,
                'length' => $fileSize,
                'isRange' => false
            ];
        }
        
        // More flexible range parsing
        if (preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
            $start = (int) $matches[1];
            $end = !empty($matches[2]) ? (int) $matches[2] : $fileSize - 1;
            
            \Log::info('VideoStreamingService: Range parsed successfully', [
                'start' => $start,
                'end' => $end,
                'fileSize' => $fileSize
            ]);
            
            // Validate range
            if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
                \Log::warning('VideoStreamingService: Invalid range requested', [
                    'start' => $start,
                    'end' => $end,
                    'fileSize' => $fileSize
                ]);
                abort(416, 'Requested range not satisfiable');
            }

            return [
                'start' => $start,
                'end' => $end,
                'length' => $end - $start + 1,
                'isRange' => true
            ];
        }
        
        \Log::info('VideoStreamingService: Range header format not recognized', ['range' => $rangeHeader]);
        
        // No valid range requested, return full file
        return [
            'start' => 0,
            'end' => $fileSize - 1,
            'length' => $fileSize,
            'isRange' => false
        ];
    }

    /**
     * Get streaming headers for iOS AVPlayer compatibility
     */
    private function getStreamingHeaders(string $mimeType, int $fileSize, array $range): array
    {
        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($range['isRange']) {
            $headers['Content-Range'] = "bytes {$range['start']}-{$range['end']}/{$fileSize}";
            $headers['Content-Length'] = $range['length'];
        } else {
            $headers['Content-Length'] = $fileSize;
        }

        return $headers;
    }

    /**
     * Create streamed response with proper chunking
     */
    private function createStreamedResponse($disk, string $path, array $range, array $headers): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($disk, $path, $range) {
            $stream = $disk->readStream($path);
            
            // Seek to start position if range request
            if ($range['isRange'] && $range['start'] > 0) {
                fseek($stream, $range['start']);
            }

            $bytesRemaining = $range['length'];
            $chunkSize = 8192; // 8KB chunks for optimal streaming

            while ($bytesRemaining > 0 && !feof($stream)) {
                $currentChunkSize = min($chunkSize, $bytesRemaining);
                $chunk = fread($stream, $currentChunkSize);
                
                if ($chunk === false) {
                    break;
                }

                echo $chunk;
                $bytesRemaining -= strlen($chunk);
                
                // Flush output buffer for real-time streaming
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }

            fclose($stream);
        });

        // Set all headers
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
        
        // Set status code for range requests
        if ($range['isRange']) {
            $response->setStatusCode(206);
            \Log::info('VideoStreamingService: Setting status code 206 for range request');
        } else {
            \Log::info('VideoStreamingService: Setting status code 200 for full file');
        }
        
        \Log::info('VideoStreamingService: Final response status', ['status' => $response->getStatusCode()]);

        return $response;
    }

    /**
     * Check if media is a video file
     */
    public function isVideo(Media $media): bool
    {
        $videoMimeTypes = [
            'video/mp4',
            'video/avi',
            'video/mov',
            'video/wmv',
            'video/flv',
            'video/webm',
            'video/mkv',
            'video/3gp',
            'video/ogg'
        ];

        return in_array($media->mime_type, $videoMimeTypes);
    }

    /**
     * Get video file info for debugging
     */
    public function getVideoInfo(Media $media): array
    {
        $disk = Storage::disk($media->disk);
        $path = $media->getPath();
        
        return [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $disk->exists($path) ? $disk->size($path) : 0,
            'disk' => $media->disk,
            'path' => $path,
            'exists' => $disk->exists($path),
            'is_video' => $this->isVideo($media)
        ];
    }
}

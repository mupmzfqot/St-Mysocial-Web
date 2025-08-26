<?php

namespace App\Observers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Services\VideoStreamingService;

class MediaObserver
{
    protected VideoStreamingService $videoStreamingService;

    public function __construct(VideoStreamingService $videoStreamingService)
    {
        $this->videoStreamingService = $videoStreamingService;
    }

    /**
     * Handle the Media "retrieved" event.
     */
    public function retrieved(Media $media): void
    {
        // Override URL for video files to use streaming route
        if ($this->videoStreamingService->isVideo($media)) {
            $media->setAttribute('original_url', $media->getUrl());
            $media->setAttribute('streaming_url', url("/stream-video/{$media->file_name}"));
        }
    }

    /**
     * Handle the Media "created" event.
     */
    public function created(Media $media): void
    {
        //
    }

    /**
     * Handle the Media "updated" event.
     */
    public function updated(Media $media): void
    {
        //
    }

    /**
     * Handle the Media "deleted" event.
     */
    public function deleted(Media $media): void
    {
        //
    }

    /**
     * Handle the Media "force deleted" event.
     */
    public function forceDeleted(Media $media): void
    {
        //
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\VideoStreamingService;

class MediaResource extends JsonResource
{
    protected VideoStreamingService $videoStreamingService;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->videoStreamingService = app(VideoStreamingService::class);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'collection_name' => $this->collection_name,
            'disk' => $this->disk,
            'conversions_disk' => $this->conversions_disk,
            'manipulations' => $this->manipulations,
            'custom_properties' => $this->custom_properties,
            'generated_conversions' => $this->generated_conversions,
            'responsive_images' => $this->responsive_images,
            'order_column' => $this->order_column,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Check if this is a video file
        if ($this->videoStreamingService->isVideo($this->resource)) {
            // For video files, use streaming URL
            $data['url'] = url("/stream-video/{$this->file_name}");
            $data['original_url'] = $this->getUrl();
            $data['is_video'] = true;
            $data['streaming_enabled'] = true;
        } else {
            // For non-video files, use original URL
            $data['url'] = $this->getUrl();
            $data['is_video'] = false;
            $data['streaming_enabled'] = false;
        }

        return $data;
    }
}

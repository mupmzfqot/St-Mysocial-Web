<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'post_id'       => $this->post_id,
            'comment'       => $this->message,
            'user'          => [
                'id'            => $this->user->id,
                'name'          => $this->user->name,
                'email'         => $this->user->email,
                'profile_img'   => $this->user->avatar
            ],
            'media'         => $this->getMedia('comment_media')->map(function ($item) {
                $isVideo = str_starts_with($item->mime_type, 'video/');
                
                return [
                    'id'            => $item->id,
                    'filename'      => $item->file_name,
                    'preview_url'   => $item->preview_url,
                    'original_url'  => $item->original_url,
                    'extension'     => $item->extension,
                    'mime_type'     => $item->mime_type,
                    'url'           => $isVideo ? url("/stream-video/{$item->file_name}") : $item->original_url,
                    'is_video'      => $isVideo,
                    'streaming_enabled' => $isVideo,
                    'streaming_url' => $isVideo ? url("/stream-video/{$item->file_name}") : null,
                ];
            }),
            'created_at'    => $this->created_at,
            'liked'         => (bool) $this->is_liked,
            'like_count'    => (int) $this->like_count
        ];
    }
}

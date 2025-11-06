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
        // Safety check - if resource is null, return empty array
        if (!$this->resource) {
            return [];
        }
        
        return [
            'id'            => $this->id ?? null,
            'post_id'       => $this->post_id ?? null,
            'comment'       => $this->message ?? null,
            'user'          => $this->when($this->relationLoaded('user') && $this->user, function() {
                return [
                    'id'            => $this->user->id ?? null,
                    'name'          => $this->user->name ?? null,
                    'email'         => $this->user->email ?? null,
                    'profile_img'   => $this->user->avatar ?? null,
                ];
            }, function() {
                // Try lazy loading if not loaded
                try {
                    if ($this->user) {
                        return [
                            'id'            => $this->user->id ?? null,
                            'name'          => $this->user->name ?? null,
                            'email'         => $this->user->email ?? null,
                            'profile_img'   => $this->user->avatar ?? null,
                        ];
                    }
                } catch (\Exception $e) {
                    // If user relation fails, return null
                }
                return null;
            }),
            'media'         => $this->when($this->getMedia('comment_media')->isNotEmpty(), function() {
                return $this->getMedia('comment_media')->map(function ($item) {
                    if (!$item) return null;
                    
                    $isVideo = str_starts_with($item->mime_type ?? '', 'video/');
                    
                    return [
                        'id'            => $item->id ?? null,
                        'filename'      => $item->file_name ?? null,
                        'preview_url'   => $item->preview_url ?? null,
                        'original_url'  => $item->original_url ?? null,
                        'extension'     => $item->extension ?? null,
                        'mime_type'     => $item->mime_type ?? null,
                        'url'           => $isVideo ? url("/stream-video/{$item->file_name}") : ($item->original_url ?? null),
                        'is_video'      => $isVideo,
                        'streaming_enabled' => $isVideo,
                        'streaming_url' => $isVideo ? url("/stream-video/{$item->file_name}") : null,
                    ];
                })->filter();
            }, []),
            'created_at'    => $this->created_at ?? null,
            'liked'         => (bool) ($this->is_liked ?? false),
            'like_count'    => (int) ($this->like_count ?? 0)
        ];
    }
}

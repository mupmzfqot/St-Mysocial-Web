<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PostResource extends JsonResource
{
    /**
     * Get author data safely
     */
    protected function getAuthorData(): ?array
    {
        try {
            // Check if author is loaded and exists
            if ($this->relationLoaded('author') && $this->author) {
                return [
                    'id'            => $this->author->id ?? null,
                    'name'          => $this->author->name ?? null,
                    'email'         => $this->author->email ?? null,
                    'profile_img'   => $this->author->avatar ?? null,
                ];
            }
            
            // Try lazy loading if not loaded
            if ($this->author) {
                return [
                    'id'            => $this->author->id ?? null,
                    'name'          => $this->author->name ?? null,
                    'email'         => $this->author->email ?? null,
                    'profile_img'   => $this->author->avatar ?? null,
                ];
            }
        } catch (\Exception $e) {
            // If author relation fails, return null
        }
        
        return null;
    }

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
            'content'       => $this->post ?? null,
            'view_count'    => $this->view_count ?? 0,
            'like_count'    => $this->like_count ?? 0,
            'comment_count' => $this->comment_count ?? 0,
            'repost_count'  => $this->repost_count ?? 0,
            'post_type'     => $this->type ?? null,
            'created_at'    => $this->created_at ? Carbon::parse($this->created_at)->format('d F Y H:i A') : null,
            'updated_at'    => $this->updated_at ? Carbon::parse($this->updated_at)->format('d F Y H:i A') : null,
            'location'      => $this->location ?? null,
            'feeling'       => $this->feeling ?? null,
            'author'        => $this->getAuthorData(),
            'user_tags'     => $this->when($this->relationLoaded('tags') && $this->tags, function() {
                return $this->tags->select('user_id', 'name');
            }, []),
            'media'         => $this->when($this->getMedia('*')->isNotEmpty(), function() {
                return $this->getMedia('*')->map(function ($item) {
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
                    ];
                })->filter()->groupBy(function ($item) {
                    if (!$item || !isset($item['mime_type'])) return 'other';
                    
                    if (str_starts_with($item['mime_type'], 'video/')) {
                        return 'video';
                    } elseif (str_starts_with($item['mime_type'], 'image/')) {
                        return 'image';
                    } elseif ($item['mime_type'] === 'application/pdf') {
                        return 'document';
                    }
                    return 'other';
                })->map(function ($items, $type) {
                    if ($type === 'video') {
                        // For videos, use streaming URLs
                        return [
                            'type' => $type,
                            'content' => $items->pluck('url')->filter()->all(),
                        ];
                    } else {
                        // For non-videos, use original URLs
                        return [
                            'type' => $type,
                            'content' => $items->pluck('original_url')->filter()->all(),
                        ];
                    }
                })->values();
            }, []),
            'link'          => $this->link ?? null,
            'comments'      => $this->when($this->relationLoaded('comments') && $this->comments, function() {
                return CommentResource::collection($this->comments);
            }),
            'repost'        => $this->when($this->relationLoaded('repost') && $this->repost, function() {
                return new PostResource($this->repost);
            }),
            'liked'         => (bool) ($this->is_liked ?? false),
            'commented'     => (bool) ($this->commented ?? false),
            'published'     => (bool) ($this->published ?? false),
            'is_reported'   => $this->when(auth()->check(), function() {
                return \App\Models\Report::where('reporter_id', auth()->id())
                    ->where('reportable_type', \App\Models\Post::class)
                    ->where('reportable_id', $this->id)
                    ->exists();
            }, false),
        ];
    }
}

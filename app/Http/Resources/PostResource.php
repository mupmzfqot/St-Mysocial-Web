<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PostResource extends JsonResource
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
            'content'       => $this->post,
            'view_count'    => $this->view_count ?? 0,
            'like_count'    => $this->like_count ?? 0,
            'comment_count' => $this->comment_count ?? 0,
            'repost_count'  => $this->repost_count ?? 0,
            'post_type'     => $this->type,
            'created_at'    => Carbon::parse($this->created_at)->format('d F Y H:i A'),
            'updated_at'    => Carbon::parse($this->updated_at)->format('d F Y H:i A'),
            'location'      => $this->location,
            'feeling'       => $this->feeling,
            'author'        => [
                'id'            => $this->author->id,
                'name'          => $this->author->name,
                'email'         => $this->author->email,
                'profile_img'   => $this->author->avatar,
            ],
            'user_tags'     => $this->tags->select('user_id', 'name') ?? [],
            'media'         => $this->getMedia('*')->map(function ($item) {
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
                    ];
                })->groupBy(function ($item) {
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
                            'content' => $items->pluck('url')->all(), // Use streaming URL
                        ];
                    } else {
                        // For non-videos, use original URLs
                        return [
                            'type' => $type,
                            'content' => $items->pluck('original_url')->all(),
                        ];
                    }
                })->values(),
            'link'          => $this->link,
            'comments'      => CommentResource::collection($this->whenLoaded('comments')),
            'repost'        => new PostResource($this->whenLoaded('repost')),
            'liked'         => (bool) $this->is_liked,
            'commented'     => (bool) $this->commented,
            'published'     => (bool) $this->published,
        ];
    }
}

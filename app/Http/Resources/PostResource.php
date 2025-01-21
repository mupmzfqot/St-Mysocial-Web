<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'view_count'    => $this->view_count,
            'like_count'    => $this->like_count,
            'comment_count' => $this->comment_count,
            'repost_count'  => $this->repost_count ?? 0,
            'post_type'     => $this->type,
            'created_at'    => $this->created_at->diffForHumans(),
            'updated_at'    => $this->updated_at->diffForHumans(),
            'location'      => $this->location,
            'feeling'       => $this->feeling,
            'author'        => [
                'id'            => $this->author->id,
                'name'          => $this->author->name,
                'email'         => $this->author->email,
                'profile_img'   => $this->author->avatar,
            ],
            'media'         => $this->getMedia('*')->map(fn ($item) => [
                    'id'            => $item->id,
                    'filename'      => $item->file_name,
                    'preview_url'   => $item->preview_url,
                    'original_url'  => $item->original_url,
                    'extension'     => $item->extension,
                    'mime_type'     => $item->mime_type,
                ])->groupBy(function ($item) {
                    if (str_starts_with($item['mime_type'], 'video/')) {
                        return 'video';
                    } elseif (str_starts_with($item['mime_type'], 'image/')) {
                        return 'image';
                    } elseif ($item['mime_type'] === 'application/pdf') {
                        return 'document';
                    }
                    return 'other';
                })->map(function ($items, $type) {
                    return [
                        'type' => $type,
                        'content' => $items->pluck('original_url')->all(),
                    ];
                })->values(),
            'link'          => $this->link,
            'comments'      => CommentResource::collection($this->whenLoaded('comments')),
            'repost'        => new PostResource($this->whenLoaded('repost')),
            'liked'         => (bool) $this->is_liked,
            'commented'     => (bool) $this->commented,
        ];
    }
}

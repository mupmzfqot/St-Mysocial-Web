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
            'rating'        => $this->rating,
            'view_count'    => $this->view_count,
            'like_count'    => $this->like_count,
            'published'     => (bool) $this->published,
            'created_at'    => $this->created_at->diffForHumans(),
            'updated_at'    => $this->updated_at->diffForHumans(),
            'author'        => [
                'id'            => $this->author->id,
                'name'          => $this->author->name,
                'email'         => $this->author->email,
                'profile_img'   => null,
            ],
            'media'         => $this->getMedia('*')->map(fn ($item) => [
                'id'            => $item->id,
                'filename'      => $item->filename,
                'preview_url'   => $item->preview_url,
                'original_url'  => $item->original_url,
                'extension'     => $item->extension,
            ]),
            'comments'      => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
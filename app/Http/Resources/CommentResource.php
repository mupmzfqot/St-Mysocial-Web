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
                'profile_img'   => $this->avatar
            ],
            'media'         => $this->getMedia('comment_media')->map(fn ($item) => [
                'id'            => $item->id,
                'filename'      => $item->file_name,
                'preview_url'   => $item->preview_url,
                'original_url'  => $item->original_url,
                'extension'     => $item->extension,
            ]),
            'created_at'    => $this->created_at,
            'liked'         => (bool) $this->is_liked,
        ];
    }
}

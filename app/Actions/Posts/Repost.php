<?php

namespace App\Actions\Posts;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Support\Facades\Log;

class Repost
{
    public function handle($user_id, $request)
    {
        $parentPost = Post::query()->find($request->post_id);

        if (!$parentPost) {
            throw new \Exception('Parent post not found');
        }

        $repost = Post::query()->firstOrCreate([
            'repost_id' => $parentPost->id,
            'user_id' => $user_id
        ], [
            'post'  => $request->post ?? '',
            'type'  => $parentPost->type,
            'published' => $parentPost->published,
        ]);

        return new PostResource($repost->load('repost'));
    }
}

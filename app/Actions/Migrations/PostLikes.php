<?php

namespace App\Actions\Migrations;

use App\Models\Post;
use App\Models\PostLiked;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PostLikes
{
    public function handle()
    {
        $likes = DB::connection('mysql_2')->table('likes')
            ->where('removeAt', 0)
            ->get();

        foreach ($likes as $like) {
            $userExist = User::find($like->fromUserId);
            $postExist = Post::find($like->itemId);
            if($userExist && $postExist) {
                PostLiked::query()->firstOrCreate([
                    'post_id' => $postExist->id,
                    'user_id' => $userExist->id
                ]);
            }
        }
    }
}

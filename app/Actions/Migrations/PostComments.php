<?php

namespace App\Actions\Migrations;

use App\Models\Comment;
use App\Models\CommentLiked;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PostComments
{
    public function handle()
    {
        // Prepare and store post comment query
        $comments = DB::connection('mysql_2')->table('comments')
            ->where('removeAt', 0)
            ->get();

        foreach ($comments as $comment) {
            $postExist = Post::find($comment->itemId);
            $userExist = User::find($comment->fromUserId);

            if ($postExist && $userExist) {
                $newComment = Comment::query()->firstOrCreate(['id' => $comment->id,], [
                    'message' => $comment->comment,
                    'user_id' => $userExist->id,
                    'post_id' => $postExist->id,
                    'created_at' => date('Y-m-d H:i:s', $comment->createAt),
                    'updated_at' => date('Y-m-d H:i:s', $comment->createAt)
                ]);

                // upload comment media if exists
                if($comment->commentOriginImgUrl) {
                    $parsedUrl = parse_url($comment->commentOriginImgUrl);
                    $path = $parsedUrl['path'] ?? null;
                    if($path && file_exists(public_path($path))) {
                        $newComment->addMedia(public_path($parsedUrl['path']))
                            ->toMediaCollection('comment_media');
                    }
                }

                // Prepare and store comment like query
                $commentLike = DB::connection('mysql_2')->table('comments_likes')
                    ->where('itemId', $comment->id)
                    ->where('removeAt', 0)
                    ->get();

                foreach ($commentLike as $like) {
                    CommentLiked::query()->firstOrCreate([
                        'comment_id' => $like->itemId,
                        'user_id' => $like->fromUserId,
                    ]);
                }
            }
        }
    }

}

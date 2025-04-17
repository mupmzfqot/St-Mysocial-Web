<?php

namespace App\Actions\Posts;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CreateComment
{
    public function handle(Request $request): bool
    {
        DB::beginTransaction();
        try {
            $comment = Comment::query()->create([
                'post_id' => $request->post_id,
                'message' => strip_tags($request->message, '<p><b><i><a>'),
                'user_id' => auth()->id()
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $comment->addMedia($file)
                        ->toMediaCollection('comment_media');
                }
            }

            $postUser = Post::find($request->post_id)?->author;
            if(auth()->id() !== $postUser->id) {
                Notification::send($postUser, new NewComment($comment, User::find(auth()->id())));
            }

            DB::commit();
            return true;
        } Catch (\Exception $e) {
            logger($e->getMessage());
            return false;
        }
    }
}

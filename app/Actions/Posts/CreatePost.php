<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreatePost
{
    public function handle($request, $id = null)
    {
        $data = [
            'post' => $request->post('content'),
            'user_id'  => $request->user()->id,
            'published' => (bool) Auth::user()->hasAnyRole(['admin', 'user']),
        ];

        DB::beginTransaction();

        try {
            if($id) {
                $post = Post::find($id)->update($data);
            } else {
                $post = Post::query()->create($data);
            }

            if (!empty($request->file('files'))) {
                foreach ($request->file('files') as $file) {
                    $post->addMedia($file)
                        ->toMediaCollection('post_media');
                }
            }

            DB::commit();
            return $post;
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }
}

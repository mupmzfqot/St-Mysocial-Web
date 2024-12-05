<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreatePost
{
    public function handle($request, $id = null)
    {
        $published = (bool) Auth::user()->hasAnyRole(['admin', 'user']);
        if($request->post('type') == 'public') {
            $published = false;
        }

        $data = [
            'post' => $request->post('content'),
            'user_id'  => $request->user()->id,
            'published' => $published,
            'type'  => $request->post('type'),
            'location' => $request->post('location'),
            'feeling' => $request->post('feeling'),
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

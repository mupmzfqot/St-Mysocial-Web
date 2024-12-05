<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreatePost
{
    public function handle($request, $id = null)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'files.*' => [
                    'mimetypes:image/*,video/mp4,video/*',
                    'file'
                ],
                'content' => 'required',
            ]);

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


            if($id) {
                $post = Post::find($id)->update($data);
            } else {
                $post = Post::query()->create($data);
            }

            if ($request->hasFile('files')) {

                if(is_array($request->file('files'))) {
                    foreach ($request->files as $file) {
                        $post->addMedia($file)
                            ->toMediaCollection('post_media');
                    }
                } else {
                    $post->addMedia($request->file('files'))->toMediaCollection('post_media');
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

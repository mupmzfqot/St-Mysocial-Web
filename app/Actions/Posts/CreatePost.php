<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreatePost
{
    public function handle($request)
    {
        $data = [
            'post' => $request->post('content'),
            'user_id'  => auth()->id(),
            'published' => (bool)Auth::user()->hasRole('admin'),
        ];

        DB::beginTransaction();

        try {
            $post = Post::query()->create($data);

            if (!empty($request->file('files'))) {
                foreach ($request->file('files') as $file) {
                    $post->addMedia($file)
                        ->toMediaCollection('post_media');
                }
            }

            # TODO: create post group
//        if (!is_null($request->post('group'))) {
//            $role = Role::query()->whereNot('name', 'public_user')->get()->pluck('id');
//            if($request->post('group') == 'all') {
//                $role = Role::all()->pluck('id');
//            }
//            $post->group()->sync($role);
//        }

            DB::commit();
            return $post;
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}

<?php

namespace App\Actions\Posts;

use App\Models\Post;
use App\Models\PostTag;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreatePost
{
    public function handle($request, $id = null)
    {
        DB::beginTransaction();

        try {

            $validated = $request->validate([
                'content' => 'required|string|max:1000',
                'files.*' => [
                    'nullable',
                    'mimetypes:image/jpeg,image/png,image/gif,video/mp4,video/quicktime',
                    'max:10240' // 10MB
                ],
                'type' => 'required|in:st,public'
            ], [
                'type.required' => 'Post type field is required',
                'type.in' => 'Invalid post type selected. Please choose either ST User or Public'
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

            if(!empty($request->userTags)) {
                PostTag::query()->where('post_id', $post->id)->delete();
                foreach ($request->userTags as $tag) {
                    PostTag::query()->create([
                        'post_id' => $post->id,
                        'user_id'  => $tag,
                        'name'     => User::query()->find($tag)?->name
                    ]);
                }
            }

            if ($request->hasFile('files')) {
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

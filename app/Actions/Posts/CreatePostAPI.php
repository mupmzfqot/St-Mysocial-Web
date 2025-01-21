<?php

namespace App\Actions\Posts;

use App\Models\Post;
use App\Models\PostTag;
use App\Models\User;
use App\Notifications\TagUserPost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CreatePostAPI
{
    public function handle($request, $id = null)
    {
        DB::beginTransaction();

        try {

            $validated = $request->validate([
                'content' => 'required|string|max:1000',
                'images' => 'nullable|array',
                'videos' => 'nullable|array',
                'images.*' => [
                    'file',
                    'mimetypes:image/jpeg,image/png,image/gif',
                    'max:3072' // 3MB
                ],
                'videos.*' => [
                    'file',
                    'mimetypes:video/mp4,video/quicktime,video/mpeg,video/ogg,video/webm,video/avi',
                    'max:10240' // 10MB
                ],
                'type' => 'required|in:st,public',
                'link' => 'nullable',
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
                'link' => $request->post('link'),
            ];


            if($id) {
                $post = Post::find($id)->update($data);
            } else {
                $post = Post::query()->create($data);
            }

            if(!empty($request->userTags) && !auth()->user()->hasRole('admin')) {
                PostTag::query()->where('post_id', $post->id)->delete();
                foreach ($request->userTags as $tag) {
                    PostTag::query()->create([
                        'post_id' => $post->id,
                        'user_id'  => $tag,
                        'name'     => User::query()->find($tag)?->name
                    ]);
                }

                $taggedUser = User::query()->whereIn('id', $request->userTags)->get();
                Notification::send($taggedUser, new TagUserPost($post, auth()->user(), false));

            }

            if (auth()->user()->hasRole('admin')) {
                $taggedUser = User::query()->whereHas('roles', function ($query) { $query->where('name', 'user'); })->get();
                Notification::send($taggedUser, new TagUserPost($post, auth()->user(), true));
            }

            if ($request->hasFile('images')) {
                $this->addMedias($request->file('images'), $post);
            }

            if ($request->hasFile('videos')) {
                $this->addMedias($request->file('videos'), $post);
            }

            DB::commit();
            return $post;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return $e->getMessage();
        }
    }

    private function addMedias($files, Post $post): void
    {
        foreach ($files as $file) {
            if ($file->isValid()) {
                $post->addMedia($file)->toMediaCollection('post_media');
            } else {
                throw new \Exception('Invalid file upload: ' . $file->getClientOriginalName());
            }
        }
    }
}

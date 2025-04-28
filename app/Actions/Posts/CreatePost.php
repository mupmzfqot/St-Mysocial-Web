<?php

namespace App\Actions\Posts;

use App\Models\Post;
use App\Models\PostTag;
use App\Models\User;
use App\Notifications\TagUserPost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CreatePost
{
    public function handle($request, $id = null)
    {
        DB::beginTransaction();

        try {

            $validated = $request->validate([
                'content' => 'nullable|string',
                'files' => 'nullable|array',
                'files.*' => [
                    'file',
                    'mimetypes:image/jpeg,image/png,image/gif,video/mp4,
                        video/quicktime,video/mpeg,video/ogg,video/webm,video/avi,application/pdf',
                    'max:10240' // 10MB
                ],
                'type' => 'required|in:st,public'
            ], [
                'type.required' => 'Post type field is required',
                'type.in' => 'Invalid post type selected. Please choose either ST User or Public'
            ]);

            $published = (bool) Auth::user()->hasAnyRole(['admin', 'user']);
            if($request->post('type') == 'public' && !Auth::user()->hasRole('admin')) {
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

                $taggedUser = User::query()->whereIn('id', $request->userTags)->get();
                Notification::send($taggedUser, new TagUserPost($post, auth()->user(), false));

            }

            if (auth()->user()->hasRole('admin')) {
                $taggedUser = User::query()->whereHas('roles', function ($query) { $query->where('name', 'user'); })->get();
                Notification::send($taggedUser, new TagUserPost($post, auth()->user(), true));
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    if ($file->isValid()) {
                        try {
                            $post->addMedia($file)
                                ->toMediaCollection('post_media');
                        } catch (\Exception $e) {
                            Log::error('File upload failed: ' . $e->getMessage(), [
                                'file' => $file->getClientOriginalName(),
                                'error' => $e->getMessage()
                            ]);
                            throw new \Exception('Failed to upload file: ' . $file->getClientOriginalName());
                        }
                    } else {
                        throw new \Exception('Invalid file upload: ' . $file->getClientOriginalName());
                    }
                }
            }

            DB::commit();
            return $post;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $e->getMessage();
        }
    }
}

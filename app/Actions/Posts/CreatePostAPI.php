<?php

namespace App\Actions\Posts;

use App\Models\Post;
use App\Models\PostTag;
use App\Models\User;
use App\Notifications\TagUserPost;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CreatePostAPI
{
    public function handle($request)
    {
        DB::beginTransaction();

        try {

            $validated = $request->validate([
                'content' => 'nullable|string',
                'images' => 'nullable|array',
                'videos' => 'nullable|array',
                'document' => 'nullable|array',
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
                'document.*' => [
                    'file',
                    'mimetypes:application/pdf',
                    'max:10240'
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

            $post = Post::query()->create($data);

            $userTagsInput = $request->input('user_tags');
            $processedUserTags = [];

            if (!empty($userTagsInput)) {
                if (is_string($userTagsInput)) {
                    $decodedTags = json_decode($userTagsInput, true);
                    if (is_array($decodedTags)) {
                        $processedUserTags = $decodedTags;
                    }
                } elseif (is_array($userTagsInput)) {
                    $processedUserTags = $userTagsInput;
                }
            }

            // Filter out any non-scalar values from tags, as user IDs should be scalar
            $processedUserTags = array_filter($processedUserTags, 'is_scalar');

            if (!empty($processedUserTags)) {
                PostTag::query()->where('post_id', $post->id)->delete();
                foreach ($processedUserTags as $tag) {
                    PostTag::query()->create([
                        'post_id' => $post->id,
                        'user_id'  => $tag,
                        'name'     => User::query()->find($tag)?->name
                    ]);
                }

                $taggedUser = User::query()->whereIn('id', $processedUserTags)->get();
                if ($taggedUser->isNotEmpty()) {
                    Notification::send($taggedUser, new TagUserPost($post, $request->user(), false));
                }
            }

            if ($request->user()->hasRole('admin')) {
                $taggedUser = User::query()->whereHas('roles', function ($query) { $query->where('name', 'user'); })->get();
                Notification::send($taggedUser, new TagUserPost($post, $request->user(), true));
            }

            if ($request->hasFile('images')) {
                $this->addMedias($request->file('images'), $post);
            }

            if ($request->hasFile('videos')) {
                $this->addMedias($request->file('videos'), $post);
            }

            if ($request->hasFile('document')) {
                $this->addMedias($request->file('document'), $post);
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

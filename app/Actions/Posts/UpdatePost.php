<?php

namespace App\Actions\Posts;

use App\Models\Post;
use App\Models\PostTag;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UpdatePost
{
    public function handle($request, Post $post)
    {
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
            'link' => 'nullable',
        ], [
            'type.required' => 'Post type field is required',
        ]);

        if ($request->hasFile('images')) {
            $this->removeMedia($post, $request->file('images'));
            $post->addMedias($request->file('images'), $post)->toMediaCollection('post_media');
        }

        if ($request->hasFile('videos')) {
            $this->removeMedia($post, $request->file('videos'));
            $post->addMedias($request->file('videos'), $post)->toMediaCollection('post_media');
        }

        if ($request->hasFile('document')) {
            $this->removeMedia($post, $request->file('document'));
            $post->addMedias($request->file('document'), $post)->toMediaCollection('post_media');
        }

        $post->post = $request->post('content');
        $post->update();

        if(!empty($request->user_tags)) {
            PostTag::query()->where('post_id', $post->id)->delete();
            foreach ($request->user_tags as $tag) {
                PostTag::query()->create([
                    'post_id' => $post->id,
                    'user_id'  => $tag,
                    'name'     => User::query()->find($tag)?->name
                ]);
            }
        }

        return $post;

    }

    private function removeMedia($post, $files)
    {
        $requestMediaId = collect($files)->map(function ($file) { return $file['id']; })->toArray();
        $existingMediaId = $post->getMedia('post_media')->pluck('id')->toArray();
        if (count($existingMediaId) > count($requestMediaId)) {
            Media::whereIn('id', array_diff($existingMediaId, $requestMediaId))->delete();
        }
    }
}
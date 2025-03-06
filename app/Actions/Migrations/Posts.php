<?php

namespace App\Actions\Migrations;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Posts
{
    public function handle(): void
    {
        $posts = DB::connection('mysql_2')
            ->table('posts')
            ->get();

        foreach ($posts as $post) {
            $userExist = User::find($post->fromUserId);
            if($userExist) {
                $newPost = Post::query()->create([
                    'id'        => $post->id,
                    'post'      => $post->post,
                    'user_id'   => $userExist->id,
                    'published' => $post->needToModerate == 0 && $post->removeAt == 0,
                    'type'      => 'st',
                    'created_at' => date('Y-m-d H:i:s', $post->createAt),
                    'updated_at' => date('Y-m-d H:i:s', $post->createAt)
                ]);

                // Prepare post media query
                $postMedia = DB::connection('mysql_2')->table('post_images')
                    ->where('toItemId', $post->id)
                    ->get()
                    ->pluck('originImgUrl');

                // Store post media if exists
                if(!empty($postMedia)) {
                    $this->uploadPostMedia($postMedia, $newPost);
                }
            }
        }
    }

    private function uploadPostMedia($postMedia, $newPost): void
    {
        if (empty($postMedia) || !$newPost) {
            \Log::warning("Skipping media upload: postMedia is empty or newPost is null");
            return;
        }

        foreach ($postMedia as $file) {
            $parsedUrl = parse_url($file);
            $path = $parsedUrl['path'] ?? null;

            if (!$path) {
                \Log::warning("Invalid media URL: {$file}");
                continue;
            }

            $filepath = public_path($path);

            if (!file_exists($filepath)) {
                \Log::warning("File not found: {$filepath}");
                continue;
            }

            try {
                $newPost->addMedia($filepath)->toMediaCollection('post_media');
            } catch (\Exception $e) {
                \Log::error("Failed to upload media for post {$newPost->id}: " . $e->getMessage());
            }
        }
    }
}

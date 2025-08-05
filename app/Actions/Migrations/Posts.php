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

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($postMedia as $file) {
            try {
                $result = $this->processSingleMedia($file, $newPost);
                
                if ($result === 'success') {
                    $successCount++;
                } elseif ($result === 'skipped') {
                    $skippedCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                \Log::error("Unexpected error processing media: {$file} - " . $e->getMessage());
                $errorCount++;
            }
        }

        \Log::info("Media upload summary for post {$newPost->id}: Success={$successCount}, Skipped={$skippedCount}, Errors={$errorCount}");
    }

    private function processSingleMedia($file, $newPost): string
    {
        // Validate input
        if (empty($file)) {
            \Log::warning("Empty file path provided");
            return 'skipped';
        }

        // Parse URL and get file path
        $parsedUrl = parse_url($file);
        $path = $parsedUrl['path'] ?? null;

        if (!$path) {
            \Log::warning("Invalid media URL: {$file}");
            return 'skipped';
        }

        $filepath = public_path($path);
        
        // Check if file exists and is accessible
        if (!$this->isFileAccessible($filepath)) {
            return 'skipped';
        }

        // Validate file integrity
        if (!$this->isFileValid($filepath)) {
            return 'skipped';
        }

        // Check if media already exists to prevent duplicates
        if ($this->isMediaAlreadyExists($newPost, basename($filepath))) {
            \Log::info("Media already exists for post {$newPost->id}: " . basename($filepath));
            return 'skipped';
        }

        // Upload to media collection
        return $this->uploadToMediaCollection($filepath, $newPost);
    }

    private function isFileAccessible($filepath): bool
    {
        // Check if file exists
        if (!file_exists($filepath)) {
            \Log::warning("File not found: {$filepath}");
            return false;
        }

        // Check if it's a file (not directory)
        if (is_dir($filepath)) {
            \Log::warning("Path is a directory, not a file: {$filepath}");
            return false;
        }

        // Check if file is readable
        if (!is_readable($filepath)) {
            \Log::warning("File is not readable: {$filepath}");
            return false;
        }

        // Check file size
        $fileSize = filesize($filepath);
        if ($fileSize === 0) {
            \Log::warning("File is empty: {$filepath}");
            return false;
        }

        if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
            \Log::warning("File too large: {$filepath} ({$fileSize} bytes)");
            return false;
        }

        return true;
    }

    private function isFileValid($filepath): bool
    {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        // Validate image files
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $imageInfo = getimagesize($filepath);
            if ($imageInfo === false) {
                \Log::warning("Invalid image file: {$filepath}");
                return false;
            }

            // Additional PNG validation
            if ($extension === 'png') {
                $pngSignature = file_get_contents($filepath, false, null, 0, 8);
                if ($pngSignature !== "\x89PNG\r\n\x1a\n") {
                    \Log::warning("Invalid PNG signature: {$filepath}");
                    return false;
                }
            }
        }

        return true;
    }

    private function isMediaAlreadyExists($newPost, $filename): bool
    {
        $existingMedia = $newPost->getMedia('post_media');
        return $existingMedia->where('file_name', $filename)->count() > 0;
    }

    private function uploadToMediaCollection($filepath, $newPost): string
    {
        try {
            $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            $filename = basename($filepath);

            \Log::info("Uploading {$extension} file: {$filename}");

            // Add to media collection with appropriate settings
            $mediaItem = $newPost->addMedia($filepath)
                ->preservingOriginal()
                ->withCustomProperties([
                    'original_path' => $filepath,
                    'uploaded_at' => now()->toISOString(),
                    'file_size' => filesize($filepath)
                ])
                ->toMediaCollection('post_media');

            \Log::info("Successfully uploaded media for post {$newPost->id}: {$filename} (ID: {$mediaItem->id})");
            return 'success';

        } catch (\Exception $e) {
            \Log::error("Failed to upload media for post {$newPost->id}: " . $e->getMessage());
            return 'error';
        }
    }
}

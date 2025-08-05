<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class FindPostMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:find-post-media {--dry-run : Simulate the process without attaching media}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find posts with no media and attach media from the old database, optimized for performance.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('--- DRY RUN MODE --- No media will be attached.');
        }

        $this->line('Step 1: Finding posts with no media...');
        $postIdsWithNoMedia = Post::doesntHave('media')->pluck('id');

        if ($postIdsWithNoMedia->isEmpty()) {
            $this->info('All posts already have media. No action needed.');
            return 0;
        }
        $this->info("Found {$postIdsWithNoMedia->count()} posts without media.");

        $this->line('Step 2: Fetching image URLs from the secondary database...');
        $postImagesData = DB::connection('mysql_2')
            ->table('posts')
            ->whereIn('posts.id', $postIdsWithNoMedia)
            ->leftJoin('post_images', 'post_images.toItemId', '=', 'posts.id')
            ->select('posts.id as post_id', 'posts.imgUrl', 'post_images.originImgUrl')
            ->get()
            ->keyBy('post_id');
        $this->info("Fetched image data for {$postImagesData->count()} posts.");

        $this->line('Step 3: Processing and attaching media...');
        $postsToUpdate = Post::whereIn('id', $postIdsWithNoMedia)->get()->keyBy('id');
        
        $progressBar = $this->output->createProgressBar($postIdsWithNoMedia->count());
        $progressBar->start();

        $summary = [
            'processed' => 0,
            'attached' => 0,
            'skipped' => 0,
            'not_found' => 0,
            'errors' => 0,
        ];

        foreach ($postIdsWithNoMedia as $postId) {
            $summary['processed']++;
            $post = $postsToUpdate->get($postId);

            if (!$post) {
                $summary['errors']++;
                $progressBar->advance();
                continue;
            }
            
            $image = $postImagesData->get($postId);
            if (!$image) {
                $summary['not_found']++;
                $progressBar->advance();
                continue;
            }

            $imageUrl = $image->originImgUrl ?: $image->imgUrl;
            if (!$imageUrl) {
                $summary['skipped']++;
                $progressBar->advance();
                continue;
            }

            $path = parse_url($imageUrl, PHP_URL_PATH);
            $filePath = public_path($path);

            if (File::exists($filePath) && !File::isDirectory($filePath)) {
                $fileName = basename($filePath);
                if ($post->getMedia('post_media')->where('file_name', $fileName)->isEmpty()) {
                    if (!$isDryRun) {
                        try {
                            $post->addMedia($filePath)
                                ->preservingOriginal()
                                ->toMediaCollection('post_media');
                        } catch (FileDoesNotExist | FileIsTooBig | \Exception $e) {
                            $this->error("\nError for Post ID {$postId}: " . $e->getMessage());
                            $summary['errors']++;
                            continue;
                        }
                    }
                    $summary['attached']++;
                } else {
                    $summary['skipped']++; // Already has this specific media
                }
            } else {
                $summary['not_found']++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line("\n\n<info>Media attachment process completed.</info>");

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Posts Processed', $summary['processed']],
                ['Media Attached', $summary['attached']],
                ['Media Skipped (duplicate or no URL)', $summary['skipped']],
                ['Image Files Not Found', $summary['not_found']],
                ['Errors', $summary['errors']],
            ]
        );
        
        if ($isDryRun) {
            $this->warn('--- This was a DRY RUN. No actual changes were made. ---');
        }
        
        return 0;
    }
}

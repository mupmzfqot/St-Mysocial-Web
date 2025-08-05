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
    protected $description = 'Find posts with no media and attach media from the old database, with fallback for different extensions.';

    private array $summary = [
        'processed' => 0,
        'attached' => 0,
        'alt_ext_found' => 0,
        'skipped' => 0,
        'not_found' => 0,
        'errors' => 0,
    ];

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
        $postImagesData = $this->fetchImageData($postIdsWithNoMedia);
        $this->info("Fetched image data for {$postImagesData->count()} posts.");

        $this->line('Step 3: Processing and attaching media...');
        $postsToUpdate = Post::whereIn('id', $postIdsWithNoMedia)->get()->keyBy('id');
        
        $progressBar = $this->output->createProgressBar($postIdsWithNoMedia->count());
        $progressBar->start();

        foreach ($postIdsWithNoMedia as $postId) {
            $this->processPost($postId, $postsToUpdate, $postImagesData, $isDryRun);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line("\n\n<info>Media attachment process completed.</info>");
        $this->displaySummary($isDryRun);
        
        return 0;
    }

    private function fetchImageData($postIds)
    {
        return DB::connection('mysql_2')
            ->table('posts')
            ->whereIn('posts.id', $postIds)
            ->leftJoin('post_images', 'post_images.toItemId', '=', 'posts.id')
            ->select('posts.id as post_id', 'posts.imgUrl', 'post_images.originImgUrl')
            ->get()
            ->keyBy('post_id');
    }

    private function processPost($postId, $postsToUpdate, $postImagesData, $isDryRun)
    {
        $this->summary['processed']++;
        $post = $postsToUpdate->get($postId);

        if (!$post) {
            $this->summary['errors']++;
            return;
        }
        
        $image = $postImagesData->get($postId);
        if (!$image || !($image->originImgUrl || $image->imgUrl)) {
            $this->summary['not_found']++;
            return;
        }

        $imageUrl = $image->originImgUrl ?: $image->imgUrl;
        $filePath = $this->findFileWithFallback($imageUrl, $this->summary['alt_ext_found']);

        if ($filePath) {
            $fileName = basename($filePath);
            if ($post->getMedia('post_media')->where('file_name', $fileName)->isEmpty()) {
                if (!$isDryRun) {
                    $this->attachMedia($post, $filePath);
                }
                $this->summary['attached']++;
            } else {
                $this->summary['skipped']++;
            }
        } else {
            $this->summary['not_found']++;
        }
    }

    private function findFileWithFallback($url, &$altExtFoundCounter): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) return null;

        $fullPath = public_path($path);
        
        if (File::exists($fullPath) && !File::isDirectory($fullPath)) {
            return $fullPath;
        }
        
        $pathInfo = pathinfo($fullPath);
        $dirname = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        
        foreach ($extensions as $ext) {
            $fallbackPath = "{$dirname}/{$filename}.{$ext}";
            if (File::exists($fallbackPath) && !File::isDirectory($fallbackPath)) {
                $altExtFoundCounter++;
                return $fallbackPath;
            }
        }

        return null;
    }

    private function attachMedia(Post $post, string $filePath): void
    {
        try {
            $post->addMedia($filePath)
                ->preservingOriginal()
                ->toMediaCollection('post_media');
        } catch (FileDoesNotExist | FileIsTooBig | \Exception $e) {
            $this->error("\nError for Post ID {$post->id}: " . $e->getMessage());
            $this->summary['errors']++;
        }
    }
    
    private function displaySummary(bool $isDryRun): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Posts Processed', $this->summary['processed']],
                ['Media Attached', $this->summary['attached']],
                ['Found with Alt. Extension', $this->summary['alt_ext_found']],
                ['Media Skipped (duplicate or no URL)', $this->summary['skipped']],
                ['Image Files Not Found', $this->summary['not_found']],
                ['Errors', $this->summary['errors']],
            ]
        );
        
        if ($isDryRun) {
            $this->warn('--- This was a DRY RUN. No actual changes were made. ---');
        }
    }
}

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
        'total_media_found' => 0,
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

        $this->line('Step 2: Fetching media URLs from the secondary database...');
        $this->line('Looking for: imgUrl, originImgUrl, videoUrl, docUrl');
        $postImagesData = $this->fetchImageData($postIdsWithNoMedia);
        $this->info("Fetched media data for {$postImagesData->count()} posts.");

        $this->line('Step 3: Processing and attaching media...');
        $this->line('Each post can have multiple media files (images, videos, documents)');
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
            ->select('posts.id as post_id', 'posts.imgUrl', 'post_images.originImgUrl', 'posts.videoUrl', 'posts.docUrl')
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
        if (!$image) {
            $this->summary['not_found']++;
            return;
        }

        // Buat array dari semua URL yang tersedia
        $mediaUrls = $this->collectMediaUrls($image);
        
        if (empty($mediaUrls)) {
            $this->summary['not_found']++;
            return;
        }

        // Proses setiap media URL
        foreach ($mediaUrls as $mediaUrl) {
            $filePath = $this->findFileWithFallback($mediaUrl, $this->summary['alt_ext_found']);

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
    }

    /**
     * Mengumpulkan semua URL media yang tersedia dari data post
     * Memastikan tidak ada nilai null dalam array
     */
    private function collectMediaUrls($image): array
    {
        $mediaUrls = [];
        
        // Tambahkan imgUrl jika ada dan tidak null
        if (!empty($image->imgUrl)) {
            $mediaUrls[] = $image->imgUrl;
        }
        
        // Tambahkan originImgUrl jika ada dan tidak null
        if (!empty($image->originImgUrl)) {
            $mediaUrls[] = $image->originImgUrl;
        }
        
        // Tambahkan videoUrl jika ada dan tidak null
        if (!empty($image->videoUrl)) {
            $mediaUrls[] = $image->videoUrl;
        }
        
        // Tambahkan docUrl jika ada dan tidak null
        if (!empty($image->docUrl)) {
            $mediaUrls[] = $image->docUrl;
        }
        
        // Update counter untuk total media yang ditemukan
        $this->summary['total_media_found'] += count($mediaUrls);
        
        // Hapus duplikat dan nilai kosong
        return array_filter(array_unique($mediaUrls));
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
        
        // Tentukan ekstensi berdasarkan tipe file yang diharapkan
        $extensions = $this->getFileExtensions($url);
        
        foreach ($extensions as $ext) {
            $fallbackPath = "{$dirname}/{$filename}.{$ext}";
            if (File::exists($fallbackPath) && !File::isDirectory($fallbackPath)) {
                $altExtFoundCounter++;
                return $fallbackPath;
            }
        }

        return null;
    }

    /**
     * Mendapatkan ekstensi file yang sesuai berdasarkan URL
     */
    private function getFileExtensions($url): array
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        // Jika sudah ada ekstensi, gunakan itu
        if ($extension) {
            return [$extension];
        }
        
        // Fallback extensions berdasarkan konteks
        if (str_contains($url, 'video') || str_contains($url, 'mp4') || str_contains($url, 'avi')) {
            return ['mp4', 'avi', 'mov', 'wmv', 'flv'];
        } elseif (str_contains($url, 'doc') || str_contains($url, 'pdf')) {
            return ['pdf', 'doc', 'docx', 'txt'];
        } else {
            // Default untuk image
            return ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        }
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
                ['Total Media URLs Found', $this->summary['total_media_found']],
                ['Media Attached', $this->summary['attached']],
                ['Found with Alt. Extension', $this->summary['alt_ext_found']],
                ['Media Skipped (duplicate or no URL)', $this->summary['skipped']],
                ['Media Files Not Found', $this->summary['not_found']],
                ['Errors', $this->summary['errors']],
            ]
        );
        
        if ($isDryRun) {
            $this->warn('--- This was a DRY RUN. No actual changes were made. ---');
        }
    }
}

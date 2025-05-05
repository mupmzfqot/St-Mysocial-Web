<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear {date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Database from given date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date');
        $formattedDate = Carbon::parse($date)->toDateTime();

        if(!$date) {
            $this->info("Please input date");
            return;
        }

        DB::beginTransaction();
        try {
            $removedPost = Post::query()
                ->where('created_at', '>=', $formattedDate)
                ->get();

            $removedPost->each(function ($post) {
                $post->comments()->delete();
                $post->likes()->delete();
                $post->reposts()->delete();
                $post->delete();
            });

            $removedMessages = Message::query()
                ->where('created_at', '>=', $formattedDate)
                ->delete();

            $removedNotification = DB::table('notifications')
                ->where('created_at', '>=', $formattedDate)
                ->delete();
            
            $this->info('Database has been cleared');

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
    }
}

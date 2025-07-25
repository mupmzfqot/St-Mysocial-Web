<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Post;
use App\Models\Comment;
use App\Models\PostLiked;
use App\Observers\PostObserver;
use App\Observers\CommentObserver;
use App\Observers\PostLikedObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Post::observe(PostObserver::class);
        Comment::observe(CommentObserver::class);
        PostLiked::observe(PostLikedObserver::class);
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;
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

        // Register broadcast routes for hybrid authentication
        $this->registerBroadcastRoutes();
    }

    /**
     * Register broadcast routes for both web and API authentication
     */
    protected function registerBroadcastRoutes(): void
    {
        // Web routes (session-based authentication) - for existing web frontend
        Broadcast::routes(['middleware' => ['web']]);
        
        // API routes (token-based authentication) - for mobile apps
        Broadcast::routes([
            'middleware' => ['api', \App\Http\Middleware\WebSocketAuth::class], 
            'prefix' => 'api'
        ]);
    }
}

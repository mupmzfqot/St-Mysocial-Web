<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use App\Models\Post;
use App\Models\Comment;
use App\Models\PostLiked;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Observers\PostObserver;
use App\Observers\CommentObserver;
use App\Observers\PostLikedObserver;
use App\Observers\MediaObserver;
use App\Http\Middleware\VideoStreamingMiddleware;

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
        Media::observe(MediaObserver::class);

        // Register broadcast routes for hybrid authentication
        $this->registerBroadcastRoutes();
        
        // Register video streaming middleware for media files
        $this->registerVideoStreamingRoutes();
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

    /**
     * Register video streaming routes with middleware
     */
    protected function registerVideoStreamingRoutes(): void
    {
        // Note: Video streaming middleware is registered globally in bootstrap/app.php
        // It will intercept all storage requests automatically
    }
}

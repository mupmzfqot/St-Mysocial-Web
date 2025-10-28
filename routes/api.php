<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/forgot-password', [AuthController::class, 'sendResetPasswordLink']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('refresh-token', [AuthController::class, 'refresh_token'])->middleware('throttle:5,1');

    Route::resource('user-profile', ProfileController::class)->only(['index', 'update']);
    Route::post('fcm-token', [UserController::class, 'updateFcmToken']);
    Route::resource('posts', PostController::class);
    Route::post('posts/{post_id}/delete', [PostController::class, 'deletePost']);
    Route::get('profile', [ProfileController::class, 'get']);
    Route::post('profile/change-password', [ProfileController::class, 'changePassword']);
    Route::post('profile/update', [ProfileController::class, 'update']);
    Route::post('update-profile-image', [ProfileController::class, 'updateProfileImage']);
    Route::post('update-profile-cover', [ProfileController::class, 'updateProfileCover']);
    Route::get('my-posts', [ProfileController::class, 'getPosts']);
    Route::get('liked-posts', [ProfileController::class, 'likedPosts']);

    Route::get('top-posts', [PostController::class, 'topPosts']);
    Route::post('posts/comment', [PostController::class, 'storeComments']);
    Route::get('posts/{id}/comment', [PostController::class, 'getComments']);
    Route::get('posts/liked-by/{id}', [PostController::class, 'likedBy']);
    Route::get('posts/user-post/get', [PostController::class, 'getUserPosts']);
    Route::get('posts/{id}/comment', [PostController::class, 'getComments']);
    Route::post('posts/like', [PostController::class, 'storeLike']);
    Route::post('posts/like-comment', [PostController::class, 'storeCommentLike']);
    Route::post('posts/delete-comment/{id}', [PostController::class, 'deleteComment']);
    Route::post('posts/unlike', [PostController::class, 'unlikePost']);
    Route::post('posts/unlike-comment', [PostController::class, 'unlikeComment']);
    Route::post('posts/repost', [PostController::class, 'repost']);
    Route::post('posts/update/{post_id}', [PostController::class, 'update']);
    Route::post('posts/media/delete', [PostController::class, 'deleteMediaByUrl']);

    Route::group(['prefix' => 'message'], function () {
        Route::get('conversation-list', [MessageController::class, 'conversationList']);
        Route::get('get', [MessageController::class, 'getConversation']);
        Route::post('send', [MessageController::class, 'sendMessage']);
        Route::post('conversation', [MessageController::class, 'openConversation']);
        Route::post('mark-as-read/{conversation_id}', [MessageController::class, 'markAsRead']);
    });

    Route::get('search-user', [UserController::class, 'searchUser']);
    Route::get('teams', [UserController::class, 'getTeams']);
    Route::get('get-albums', [UserController::class, 'getMedia']);

    // Optimized video routes with caching
    Route::prefix('video')->group(function () {
        Route::get('get', [UserController::class, 'getVideo']);
        Route::get('get-cached', [UserController::class, 'getVideoCached']);
        Route::post('clear-cache', [UserController::class, 'clearVideoCache']);
    });

    // Video streaming routes for mobile app
    Route::prefix('stream')->group(function () {
        Route::get('video/{filename}', function ($filename) {
            // Find media by filename
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('file_name', $filename)->first();
            
            if (!$media) {
                abort(404, 'Video not found');
            }
            
            $videoService = app(\App\Services\VideoStreamingService::class);
            if (!$videoService->isVideo($media)) {
                abort(400, 'Not a video file');
            }
            
            return $videoService->streamVideo($media, request());
        })->name('api.stream.video')->where('filename', '.*');
        
        Route::get('media/{mediaId}', function ($mediaId) {
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId);
            if (!$media) {
                abort(404, 'Media not found');
            }
            
            $videoService = app(\App\Services\VideoStreamingService::class);
            if (!$videoService->isVideo($media)) {
                abort(400, 'Not a video file');
            }
            
            return $videoService->streamVideo($media, request());
        })->name('api.stream.media');
    });

    Route::group(['prefix' => 'notification'], function () {
        Route::get('/get', [NotificationController::class, 'index']);
        Route::post('/markasread', [NotificationController::class, 'markAsRead']);
    });
});

// WebSocket test route
Route::middleware('auth:sanctum')->get('/websocket/test', function () {
    return response()->json([
        'message' => 'WebSocket authentication working',
        'user' => auth()->user()->only(['id', 'name', 'email']),
        'timestamp' => now()->toISOString()
    ]);
});

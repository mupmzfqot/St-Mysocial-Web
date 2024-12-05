<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    Route::resource('user-profile', ProfileController::class)->only(['index', 'update']);
    Route::resource('posts', PostController::class);

    Route::get('top-posts', [PostController::class, 'topPosts']);
    Route::post('posts/comment', [PostController::class, 'storeComment']);
    Route::post('posts/like', [PostController::class, 'storeLike']);

    Route::get('my-posts', [ProfileController::class, 'getPosts']);
    Route::get('liked-posts', [ProfileController::class, 'likedPosts']);

    Route::group(['prefix' => 'message'], function () {
        Route::get('conversation-list', [MessageController::class, 'conversationList']);
        Route::get('get', [MessageController::class, 'getConversation']);
        Route::post('send', [MessageController::class, 'sendMessage']);
    });

    Route::get('search-user', [UserController::class, 'searchUser']);
    Route::get('get-albums', [UserController::class, 'getMedia']);
});

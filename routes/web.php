<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PhotoAlbumController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostModerationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('home');

Route::middleware(['auth', 'verified', 'role:user|public_user'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('homepage')->middleware('role:user');
    Route::get('/public', [HomeController::class, 'publicPost'])->name('public');
    Route::get('/posts', [HomeController::class, 'createPost'])->name('create-post');
    Route::get('/liked-posts', [HomeController::class, 'showLikedPosts'])->name('liked-posts');
    Route::get('/top-posts', [HomeController::class, 'showTopPosts'])->name('top-posts');
    Route::prefix('user-post')->name('user-post.')->group(function () {
        Route::get('get', [PostController::class, 'get'])->name('get');
        Route::post('store', [PostController::class, 'store'])->name('store');
        Route::post('share', [PostController::class, 'share'])->name('share');
        Route::post('store', [PostController::class, 'store'])->name('store');
        Route::get('show/{id}', [HomeController::class, 'showPost'])->name('show-post');
        Route::get('liked-by/{id}', [HomeController::class, 'postLikedBy'])->name('liked-by');
        Route::post('delete', [HomeController::class, 'deletePost'])->name('delete');
        Route::post('comment', [HomeController::class, 'storeComment'])->name('store-comment');
        Route::post('like', [HomeController::class, 'storeLike'])->name('send-like');
        Route::post('unlike', [HomeController::class, 'unlike'])->name('unlike');
        Route::post('like-comment', [HomeController::class, 'storeCommentLike'])->name('send-comment-like');
        Route::post('unlike-comment', [HomeController::class, 'unlikeComment'])->name('unlike-comment');
        Route::post('delete-comment', [HomeController::class, 'deleteComment'])->name('delete-comment');
    });

    Route::get('st-user', [UserController::class, 'stIndex'])->name('st-user');
    Route::get('notifications', [HomeController::class, 'notifications'])->name('notifications');

});

Route::get('registration-success', [UserController::class, 'registrationSuccess'])
    ->middleware('auth')->name('registration-success');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/show/{id?}', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/update-status-image/{id}', [ProfileController::class, 'updateProfileImageStatus'])->name('profile.update-status-image');

    Route::prefix('photo-album')->name('photoAlbum.')->group(function () {
        Route::get('/', [PhotoAlbumController::class, 'index'])->name('index');
    });

    Route::post('upload-profile-image', [ProfileController::class, 'uploadImage'])->name('profile.upload-image');

    Route::prefix('message')->name('message.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/show/{id}', [MessageController::class, 'openConversation'])->name('show');
        Route::post('/send/{id}', [MessageController::class, 'sendMessage'])->name('send');
        Route::post('/mark-as-read/{conversation_id}', [MessageController::class, 'markAsRead'])->name('mark-as-read');
        Route::get('/unread-count', [MessageController::class, 'getUnreadCount'])->name('unread-count');
    })->middleware('role:user');

    Route::get('team-st', [TeamController::class, 'get'])->name('team.get');
    Route::get('search', [UserController::class, 'search'])->name('user.search');

    Route::post('read-notification/{id?}', [NotificationController::class, 'readNotification'])->name('read-notification');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/active-users', [DashboardController::class, 'showActiveUsers'])->name('active-users');
    Route::get('/most-liked-posts', [DashboardController::class, 'showMostLikedPosts'])->name('most-liked-posts');
    Route::get('/most-comment-posts', [DashboardController::class, 'showMostCommentPosts'])->name('most-comment-posts');
    Route::get('/most-user-posts', [DashboardController::class, 'showMostUserPost'])->name('most-user-posts');
    Route::get('/user-posts', [DashboardController::class, 'showUserPosts'])->name('user-posts');

    Route::name('admin.')->prefix('admin')->group(function () {
        Route::get('/', [UserController::class, 'adminIndex'])->name('index');
        Route::get('form/{id?}', [UserController::class, 'adminForm'])->name('form');
        Route::post('store', [UserController::class, 'store'])->name('store');
        Route::post('update-status/{id}', [UserController::class, 'updateActiveStatus'])->name('update-status');
        Route::get('pending-approval', [DashboardController::class, 'getPendingApprovals'])->name('pending-approval');
    });

    Route::name('user.')->prefix('user')->group(function () {
        Route::get('/get', [UserController::class, 'get'])->name('get');
        Route::get('/', [UserController::class, 'userIndex'])->name('index');
        Route::get('public-account', [UserController::class, 'publicAccountIndex'])->name('public');
        Route::post('delete/{id}', [UserController::class, 'destroy'])->name('delete');
        Route::get('profile/{id?}', [ProfileController::class, 'index'])->name('profile');
        Route::post('set-admin/{id}', [UserController::class, 'setAsAdmin'])->name('set-admin');
        Route::post('reset-password/{id}', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::post('update/{id}', [UserController::class, 'update'])->name('update');
        Route::post('verify/{id}', [UserController::class, 'verifyAccount'])->name('verify');
    });

    Route::name('post.')->prefix('post')->group(function () {
        Route::get('/index', [PostController::class, 'index'])->name('index');
        Route::get('create', [PostController::class, 'create'])->name('create');
        Route::get('edit/{id}', [PostController::class, 'edit'])->name('edit');
        Route::post('store', [PostController::class, 'store'])->name('store');
        Route::post('update/{id}', [PostController::class, 'update'])->name('update');
        Route::post('delete/{id}', [PostController::class, 'destroy'])->name('delete');
    });

    Route::name('post-moderation.')->prefix('post-moderation')->group(function () {
        Route::get('/', [PostModerationController::class, 'index'])->name('index');
        Route::post('update-status/{id}', [PostModerationController::class, 'updateStatus'])->name('update-status');
    });

    Route::get('profile-photos', [ProfileController::class, 'indexPhotos'])->name('profile-photos');
    Route::get('profile-covers', [ProfileController::class, 'indexCovers'])->name('profile-covers');


    Route::get('app-setting', [SettingController::class, 'index'])->name('app-setting');
});


require __DIR__.'/auth.php';

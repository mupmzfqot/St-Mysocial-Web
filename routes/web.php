<?php

use App\Http\Controllers\ChangePasswordController;
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
    Route::get('/posts/{id}', [HomeController::class, 'editPost'])->name('edit-post');
    Route::get('/liked-posts', [HomeController::class, 'showLikedPosts'])->name('liked-posts');
    Route::get('/top-posts', [HomeController::class, 'showTopPosts'])->name('top-posts');
    Route::prefix('user-post')->name('user-post.')->group(function () {
        Route::get('get', [PostController::class, 'get'])->name('get');
        Route::get('top-post', [PostController::class, 'getTopPost'])->name('get-top-post');
        Route::get('liked-post', [PostController::class, 'getLikedPost'])->name('liked-post');
        Route::get('recent-post', [PostController::class, 'getRecentPost'])->name('recent-post');
        Route::get('tag-post', [PostController::class, 'getTagPost'])->name('tag-post');
        Route::post('store', [PostController::class, 'store'])->name('store');
        Route::post('update/{id}', [PostController::class, 'update'])->name('update');
        Route::post('delete', [HomeController::class, 'deletePost'])->name('delete');
       
    });

    Route::get('st-user', [UserController::class, 'stIndex'])->name('st-user');
    Route::get('change-password', [ChangePasswordController::class, 'index'])->name('change-password.index');
    Route::post('change-password', [ChangePasswordController::class, 'store'])->name('change-password.store');
});

Route::get('registration-success', [UserController::class, 'registrationSuccess'])
    ->middleware('auth')->name('registration-success');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/show/{id?}', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/update-status-image/{id}', [ProfileController::class, 'updateProfileImageStatus'])->name('profile.update-status-image');
    
    Route::post('user-post/comment', [HomeController::class, 'storeComment'])->name('user-post.store-comment');
    Route::post('like', [HomeController::class, 'storeLike'])->name('user-post.send-like');
    Route::post('unlike', [HomeController::class, 'unlike'])->name('user-post.unlike');
    Route::post('like-comment', [HomeController::class, 'storeCommentLike'])->name('user-post.send-comment-like');
    Route::post('unlike-comment', [HomeController::class, 'unlikeComment'])->name('user-post.unlike-comment');
    Route::post('delete-comment', [HomeController::class, 'deleteComment'])->name('user-post.delete-comment');
    Route::get('get-post/{id}', [PostController::class, 'postById'])->name('user-post.get-post');
    Route::post('share', [PostController::class, 'share'])->name('user-post.share');
    Route::get('liked-by/{id}', [HomeController::class, 'postLikedBy'])->name('user-post.liked-by');
    
    Route::get('/photo-album', [PhotoAlbumController::class, 'index'])->name('photoAlbum.index');
    Route::get('/videos', [PhotoAlbumController::class, 'videos'])->name('videos.index');

    Route::get('user-post/show/{id}', [HomeController::class, 'showPost'])->name('user-post.show-post');
    Route::get('user-post/tagged-user/{id}', [PostController::class, 'getTaggedUser'])->name('user-post.tagged-user');
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
    Route::get('notifications', [HomeController::class, 'notifications'])->name('notifications');
    Route::post('read-notification/{id?}', [NotificationController::class, 'readNotification'])->name('read-notification');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/active-users', [DashboardController::class, 'showActiveUsers'])->name('active-users');
    Route::get('/most-liked-posts', [DashboardController::class, 'showMostLikedPosts'])->name('most-liked-posts');
    Route::get('/most-comment-posts', [DashboardController::class, 'showMostCommentPosts'])->name('most-comment-posts');
    Route::get('/most-user-posts', [DashboardController::class, 'showMostUserPost'])->name('most-user-posts');
    Route::get('/user-posts', [DashboardController::class, 'showUserPosts'])->name('user-posts');
    Route::get('/account/{type}', [DashboardController::class, 'getAccount'])->name('account');

    Route::name('admin.')->prefix('admin')->group(function () {
        Route::get('/', [UserController::class, 'adminIndex'])->name('index');
        Route::get('form/{id?}', [UserController::class, 'adminForm'])->name('form');
        Route::post('store', [UserController::class, 'store'])->name('store');
        Route::post('update-status/{id}', [UserController::class, 'updateActiveStatus'])->name('update-status');
        Route::get('pending-approvals', [DashboardController::class, 'getPendingApprovals'])->name('pending-approvals');
    });

    Route::name('user.')->prefix('user')->group(function () {
        Route::get('/get', [UserController::class, 'get'])->name('get');
        Route::get('/', [UserController::class, 'userIndex'])->name('index');
        Route::get('public-account', [UserController::class, 'publicAccountIndex'])->name('public');
        Route::post('delete/{id}', [UserController::class, 'destroy'])->name('delete');
        Route::get('profile/{id?}', [ProfileController::class, 'index'])->name('profile');
        Route::post('set-admin/{id}', [UserController::class, 'setAsAdmin'])->name('set-admin');
        Route::post('set-user/{id}', [UserController::class, 'setAsUser'])->name('set-user');
        Route::post('reset-password/{id}', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::post('update/{id}', [UserController::class, 'update'])->name('update');
        Route::post('verify/{id}', [UserController::class, 'verifyAccount'])->name('verify');
    });

    Route::name('post.')->prefix('post')->group(function () {
        Route::get('/index', [PostController::class, 'index'])->name('index');
        Route::get('create', [PostController::class, 'create'])->name('create');
        Route::get('edit/{id}', [PostController::class, 'edit'])->name('edit');
        Route::get('show/{id}', [PostController::class, 'show'])->name('show');
        Route::post('store', [PostController::class, 'store'])->name('store');
        Route::post('update/{id}', [PostController::class, 'update'])->name('update');
        Route::post('delete/{id}', [PostController::class, 'destroy'])->name('delete');
    });

    Route::name('post-moderation.')->prefix('post-moderation')->group(function () {
        Route::get('/', [PostModerationController::class, 'index'])->name('index');
        Route::get('/st', [PostModerationController::class, 'indexST'])->name('index-st');
        Route::post('update-status/{id}', [PostModerationController::class, 'updateStatus'])->name('update-status');
    });

    Route::get('profile-photos', [ProfileController::class, 'indexPhotos'])->name('profile-photos');
    Route::get('profile-covers', [ProfileController::class, 'indexCovers'])->name('profile-covers');

    Route::name('setting.')->prefix('setting')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/smtp/{id?}', [SettingController::class, 'save_smtp'])->name('smtp');
    });
});

require __DIR__.'/auth.php';
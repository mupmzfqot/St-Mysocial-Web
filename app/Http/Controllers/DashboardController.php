<?php

namespace App\Http\Controllers;

use App\Actions\UserCount;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLiked;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DashboardController extends Controller
{
    public function index(UserCount $userCount)
    {
        $users = $userCount->handle();
        $totalPosts = (int) Post::query()->count();
        $totalActivePosts = (int) Post::query()->published()->count();
        $totalComments = (int) Comment::whereHas('post')->count();
        $totalLikes = (int) PostLiked::whereHas('post')->count();
        $totalPhotos = (int) Media::count();
        $totalMessages = (int) 0;
        $totalAccounts = (int) User::query()->count();
        $totalActiveAccounts = (int) User::query()->whereNull('deleted_at')->where('is_active', 1)->count();
        $totalBlockedAccounts = (int) User::query()->where('is_active', 0)->count();

        return Inertia::render('Dashboard', compact(
            'users', 'totalPosts', 'totalActivePosts', 'totalComments', 'totalLikes', 'totalPhotos', 'totalMessages',
            'totalAccounts', 'totalActiveAccounts', 'totalBlockedAccounts'
        ));
    }
}

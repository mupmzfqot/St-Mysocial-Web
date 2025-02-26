<?php

namespace App\Http\Controllers;

use App\Actions\UserCount;
use App\Models\Comment;
use App\Models\Message;
use App\Models\Post;
use App\Models\PostLiked;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
        $totalMessages = (int) Message::count();
        $totalAccounts = (int) User::query()->where('is_active', 1)->count();
        $totalActiveAccounts = (int) User::query()->whereNull('deleted_at')->where('is_active', 1)->count();
        $totalBlockedAccounts = (int) User::query()->where('is_active', 0)->whereNull('deleted_at')->count();

        return Inertia::render('Dashboard', compact(
            'users', 'totalPosts', 'totalActivePosts', 'totalComments', 'totalLikes', 'totalPhotos', 'totalMessages',
            'totalAccounts', 'totalActiveAccounts', 'totalBlockedAccounts'
        ));
    }

    public function getPendingApprovals()
    {
        $pendingUsers = User::where('is_active', 0)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'public_user');
            })->count();

        $pendingPosts = Post::where('published', 0)
            ->count();

        return response()->json([
            'users' => $pendingUsers,
            'posts' => $pendingPosts,
        ]);
    }

    public function showActiveUsers(Request $request)
    {
        $searchTerm = $request->search;
        $users = User::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->where('is_login', 1)
            ->orderBy('updated_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Dashboard/ActiveUsers', compact('users', 'searchTerm'));
    }

    public function showMostLikedPosts(Request $request)
    {
        $searchTerm = $request->search;
        $topPosts = Post::query()
            ->when($request->search, function ($query, $search) {
                $query->where('post', 'like', '%' . $search . '%');
            })
            ->with(['author', 'media'])
            ->withCount('likes')
            ->orderBy('likes_count', 'desc')
            ->limit(100)
            ->get();

        $perPage = 20;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $items = $topPosts->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $posts = new LengthAwarePaginator(
            $items,
            $topPosts->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        $posts = $this->pagination($topPosts);

        return Inertia::render('Dashboard/MostLikedPosts', compact('posts', 'searchTerm'));
    }

    public function showMostCommentPosts(Request $request)
    {
        $searchTerm = $request->search;
        $topPosts = Post::query()
            ->when($searchTerm, function ($query, $search) {
                $query->where('post', 'like', '%' . $search . '%');
            })
            ->with(['author', 'media'])
            ->withCount('comments')
            ->orderBy('comments_count', 'desc')
            ->limit(100)
            ->get();

        $posts = $this->pagination($topPosts);

        return Inertia::render('Dashboard/MostCommentPosts', compact('posts', 'searchTerm'));
    }

    public function showMostUserPost(Request $request)
    {
        $searchTerm = $request->search;
        $userPosts = User::query()
            ->isActive()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->withCount(['posts as posts_count' => function ($query) {
                $query->where('published', 1);
            }])
            ->orderBy('posts_count', 'desc')
            ->whereHas('posts', function ($query) {
                $query->where('published', 1);
            })
            ->limit(100)
            ->get();

        $users = $this->pagination($userPosts);
        return Inertia::render('Dashboard/MostUserPosts', compact('users', 'searchTerm'));
    }

    public function showUserPosts(Request $request)
    {
        $searchTerm = $request->search;
        $users = User::query()
            ->isActive()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->withCount(['posts as posts_count' => function ($query) {
                $query->where('published', 1);
            }])
            ->whereHas('posts', function ($query) {
                $query->where('published', 1);
            })
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Dashboard/UserPosts', compact('users', 'searchTerm'));
    }

    private function pagination($model)
    {
        $perPage = 20;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $items = $model->slice(($currentPage - 1) * $perPage, $perPage)->values();
        return new LengthAwarePaginator(
            $items,
            $model->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    public function getAccount(Request $request, $type)
    {
        $request->validate([
            'search' => 'nullable|string'
        ]);

        $title = $request->type == 1 ? 'Registered Account' : 'Blocked Account';
        $type = in_array($type, [0,1]) ? $type : 0;

        $searchTerm = $request->search;
        $users = User::query()
            ->where('is_active', $type)
            ->when($searchTerm, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('username', 'like', '%' . $search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate('20')
            ->withQueryString();


        return Inertia::render('Dashboard/Account', compact('users', 'searchTerm', 'title', 'type'));
    }
}

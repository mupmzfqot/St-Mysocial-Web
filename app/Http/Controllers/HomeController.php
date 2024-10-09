<?php

namespace App\Http\Controllers;

use App\Actions\Posts\CreatePost;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLiked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $posts = Post::query()
            ->with('author', 'media', 'comments.user')
            ->orderBy('created_at', 'desc')
            ->published()
            ->paginate(30);

        return Inertia::render('Home', compact('posts'));
    }

    public function showLikedPosts()
    {
        $posts = Post::query()
            ->with('author', 'media', 'comments.user')
            ->orderBy('created_at', 'desc')
            ->whereHas('likes', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->published()
            ->paginate(30);

        return Inertia::render('Homepage/LikedPost', compact('posts'));
    }

    public function showPost($id)
    {
        $post = Post::query()
            ->with('author', 'media', 'comments.user')
            ->orderBy('created_at', 'desc')
            ->published()
            ->where('id', $id)
            ->first();

        return Inertia::render('Homepage/PostDetail', compact('post'));
    }

    public function createPost()
    {
        $posts = Post::query()
            ->with('author', 'media', 'comments.user')
            ->with('author', 'media', 'comments.user')
            ->orderBy('created_at', 'desc')
            ->where('user_id', auth()->id())
            ->paginate(30);

        return Inertia::render('Homepage/CreatePost', compact('posts'));
    }

    public function storePost(Request $request, CreatePost $createPost)
    {
        $createPost->handle($request);
        return redirect()->route('homepage');
    }

    public function storeComment(Request $request)
    {
        Comment::query()->create([
            'post_id' => $request->post_id,
            'message' => $request->message,
            'user_id' => auth()->id()
        ]);

    }

    public function storeLike(Request $request)
    {
        $liked = PostLiked::query()->where('post_id', $request->post_id)->where('user_id', auth()->id())->first();

        if(!$liked) {
            PostLiked::query()->create([
                'post_id' => $request->post_id,
                'user_id' => auth()->id()
            ]);
        }
    }

    public function showTopPosts()
    {
        $posts = Post::query()
            ->with('author', 'media', 'comments.user')
            ->orderBy(DB::raw('comment_count + like_count'), 'desc')
            ->where('comment_count', '>', 0)
            ->where('like_count', '>', 0)
            ->take(10)
            ->get();

        return Inertia::render('Homepage/TopPost', compact('posts'));
    }

}

<?php

namespace App\Http\Controllers;

use App\Actions\Posts\CreatePost;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLiked;
use App\Models\User;
use App\Notifications\NewComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $posts = Post::query()
            ->with('author', 'media', 'comments.user', 'tags')
            ->orderBy('created_at', 'desc')
            ->published()
            ->where('type', 'st')
            ->paginate(30);

        $title = 'ST Post';
        $description = 'Post from ST Team';

        return Inertia::render('Home', compact('posts', 'title', 'description'));
    }

    public function publicPost()
    {$posts = Post::query()
        ->with('author', 'media', 'comments.user')
        ->orderBy('created_at', 'desc')
        ->published()
        ->where('type', 'public')
        ->paginate(30);

        $title = 'Public Post';
        $description = 'Post for Public';

        return Inertia::render('Home', compact('posts', 'title', 'description'));
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
            ->orderBy('created_at', 'desc')
            ->published()
            ->paginate(30);

        $stUsers = User::query()->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->where('id', '!=', auth()->id())
            ->select('id', 'name')
            ->where('is_active', true)->get();

        $isPrivilegedUser = auth()->user()->hasAnyRole(['admin', 'user']);
        $defaultType = $isPrivilegedUser ? 'st' : 'public';

        return Inertia::render('Homepage/CreatePost', compact('posts', 'stUsers', 'defaultType'));
    }

    public function storePost(Request $request, CreatePost $createPost)
    {
        $request->validate([
            'content' => 'required|string',
            'group'   => 'required'
        ]);

        $createPost->handle($request);
        //return redirect()->route('homepage');
    }

    public function storeComment(Request $request)
    {
        $comment = Comment::query()->create([
            'post_id' => $request->post_id,
            'message' => $request->message,
            'user_id' => auth()->id()
        ]);

        $postUser = Post::find($request->post_id)?->user;
        Notification::send($postUser, new NewComment($comment, User::find(auth()->id())));


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
            ->published()
            ->paginate(30);


        return Inertia::render('Homepage/TopPost', compact('posts'));
    }

}

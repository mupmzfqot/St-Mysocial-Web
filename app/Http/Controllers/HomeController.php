<?php

namespace App\Http\Controllers;

use App\Actions\Posts\CreateComment;
use App\Actions\Posts\CreatePost;
use App\Models\Comment;
use App\Models\CommentLiked;
use App\Models\Post;
use App\Models\PostLiked;
use App\Models\User;
use App\Notifications\NewComment;
use App\Notifications\NewCommentLike;
use App\Notifications\NewLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        return Inertia::render('Home', [
            'title' =>'ST Post',
            'description' => 'Post from Team ST',
            'type'  => 'st'
        ]);
    }

    public function publicPost()
    {
        return Inertia::render('Home', [
            'title' =>'Public Post',
            'description' => 'Post for Public',
            'type'  => 'public'
        ]);
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
            ->paginate(30)
            ->through(function ($post) {
                // Ensure we have all necessary data loaded
                $post->load('likes');
                return $post;
            })
            ->withQueryString();

        return Inertia::render('Homepage/LikedPost', [
            'posts' => $posts
        ]);
    }

    public function showPost($id)
    {
        $post = Post::query()
            ->with('author', 'media', 'tags', 'comments.user', 'comments.media', 'repost.author', 'repost.media')
            ->orderBy('created_at', 'desc')
            ->published()
            ->where('id', $id)
            ->first();

        return response()->json($post);
//        return Inertia::render('Homepage/PostDetail', compact('post'));
    }

    public function createPost()
    {
        $posts = Post::query()
            ->with('author', 'media', 'comments.user')
            ->orderBy('created_at', 'desc')
            ->where('user_id', auth()->id())
            ->paginate(30)
            ->through(function ($post) {
                // Ensure we have all necessary data loaded
                $post->load('likes');
                return $post;
            })
            ->withQueryString();

        $stUsers = User::query()->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->where('id', '!=', auth()->id())
            ->select('id', 'name')
            ->whereNotNull('email_verified_at')
            ->where('is_active', true)
            ->get();

        $isPrivilegedUser = auth()->user()->hasAnyRole(['admin', 'user']);
        $defaultType = $isPrivilegedUser ? 'st' : 'public';

        return Inertia::render('Homepage/CreatePost', [
            'posts' => $posts,
            'stUsers' => $stUsers,
            'defaultType' => $defaultType
        ]);
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

    public function storeComment(Request $request, CreateComment $createComment)
    {
        $createComment->handle($request);
    }

    public function storeLike(Request $request)
    {
        $liked = PostLiked::query()->where('post_id', $request->post_id)->where('user_id', auth()->id())->first();

        if(!$liked) {
            $postLiked = PostLiked::query()->create([
                'post_id' => $request->post_id,
                'user_id' => auth()->id()
            ]);

            $post = Post::query()->find($request->post_id);
            if($post->user_id != auth()->id()) {
                Notification::send($post?->author, new NewLike($postLiked, User::find(auth()->id())));
            }
        }

    }

    public function storeCommentLike(Request $request)
    {
        $liked = CommentLiked::query()->where('comment_id', $request->comment_id)->where('user_id', auth()->id())->first();

        if(!$liked) {
            $commentLiked = CommentLiked::query()->create([
                'comment_id' => $request->comment_id,
                'user_id' => auth()->id()
            ]);

            $comment = Comment::query()->find($request->comment_id);
            if($comment->user_id != auth()->id()) {
                Notification::send($comment?->user, new NewCommentLike($comment, User::find(auth()->id())));
            }
        }

    }

    public function unlike(Request $request)
    {
        PostLiked::query()->where('post_id', $request->post_id)->where('user_id', auth()->id())->delete();
    }

    public function unlikeComment(Request $request)
    {
        CommentLiked::query()->where('comment_id', $request->comment_id)->where('user_id', auth()->id())->delete();
    }

    public function deleteComment(Request $request)
    {
        DB::beginTransaction();
        try {
            $comment = Comment::query()->find($request->content_id);
            $comment->likes()->delete();
            $comment->delete();
            DB::commit();
        } Catch (\Exception $e) {
            DB::rollBack();
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
            ->paginate(30)
            ->through(function ($post) {
                // Ensure we have all necessary data loaded
                $post->load('likes');
                return $post;
            })
            ->withQueryString();


        return Inertia::render('Homepage/TopPost', [
            'posts' => $posts
        ]);
    }

    public function notifications()
    {
        auth()->user()->unreadNotifications->markAsRead();
        $notifications = auth()->user()->notifications()->paginate(20);

        return Inertia::render('Homepage/Notifications', compact('notifications'));
    }

    public function deletePost(Request $request)
    {
        $post = Post::query()->where('user_id', auth()->id())->whereId($request->content_id)->first();
        $post->comments()->delete();
        $post->likes()->delete();
        $post->delete();
    }

    public function postLikedBy(Request $request, $postId)
    {
        $user = User::whereHas('likes', function ($query) use ($postId) {
            $query->where('post_id', $postId);
        })
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar
                ];
            });

        return response()->json($user);
    }

}

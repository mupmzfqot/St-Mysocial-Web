<?php

namespace App\Http\Controllers;

use App\Actions\Posts\CreateComment;
use App\Actions\Posts\CreatePost;
use App\Models\Comment;
use App\Models\CommentLiked;
use App\Models\Post;
use App\Models\PostLiked;
use App\Models\User;
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
            'title' =>'Home',
            'description' => 'Post from Team ST',
            'requestUrl' => route('user-post.get', ['type' => 'st']),
        ]);
    }

    public function publicPost()
    {
        return Inertia::render('Home', [
            'title' =>'Public Post',
            'description' => 'Post for Public',
            'type'  => 'public',
            'requestUrl' => route('user-post.get', ['type' => 'public']),
        ]);
    }

    public function showTopPosts()
    {
        return Inertia::render('Homepage/TopPost', [
            'requestUrl' => route('user-post.get-top-post')
        ]);
    }

    public function showLikedPosts()
    {
        return Inertia::render('Homepage/LikedPost', [
            'requestUrl' => route('user-post.liked-post')
        ]);
    }

    public function showPost($id)
    {
        if(auth()->user()->hasRole('admin')) {
            return redirect()->route('post.show', $id);
        }

        $post = Post::query()
            ->with('author', 'media', 'tags', 'comments.user', 'comments.media', 'repost.author', 'repost.media')
            ->orderBy('created_at', 'desc')
            ->published()
            ->where('id', $id)
            ->first();

        return Inertia::render('Homepage/PostDetail', compact('post'));
    }

    public function createPost()
    {
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
            'stUsers' => $stUsers,
            'defaultType' => $defaultType,
            'requestUrl' => route('user-post.recent-post')
        ]);
    }

    public function editPost($id)
    {
        $stUsers = User::query()->select('id', 'name')->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get();

        $post = Post::query()
            ->with('author', 'media', 'comments.user', 'tags', 'repost.author', 'repost.media', 'repost.tags')
            ->where('id', $id)
            ->first();

        $isPrivilegedUser = auth()->user()->hasAnyRole(['admin', 'user']);
        $defaultType = $isPrivilegedUser ? 'st' : 'public';

        return Inertia::render('Homepage/EditPost', [
            'post' => $post,
            'stUsers' => $stUsers,
            'defaultType' => $defaultType,
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

    public function notifications()
    {
        auth()->user()->unreadNotifications->markAsRead();
        $notifications = auth()->user()->notifications()->paginate(20);

        if(auth()->user()->hasRole('admin')) {
            return Inertia::render('Dashboard/Notification', compact('notifications'));
        }

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

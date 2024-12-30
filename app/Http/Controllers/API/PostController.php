<?php

namespace App\Http\Controllers\API;

use App\Actions\Posts\CreateComment;
use App\Actions\Posts\CreatePost;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Comment;
use App\Models\CommentLiked;
use App\Models\Post;
use App\Models\PostLiked;
use App\Models\User;
use App\Notifications\NewCommentLike;
use App\Notifications\NewLike;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $searchTerm = $request->query('search');
        $perPage = $request->query('per_page', 20);
        $posts = Post::query()
            ->when($searchTerm, function ($query, $search) {
                $query->where('post', 'like', '%' . $search . '%');
            })
            ->where('type', $request->query('type'))
            ->published()
            ->with(['author', 'media'])
            ->paginate($perPage);

        return PostResource::collection($posts);
    }

    public function show($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        return new PostResource($post->load('comments'));
    }

    public function store(Request $request, CreatePost $createPost)
    {
        $created = $createPost->handle($request);
        return new PostResource($created);
    }

    public function update(Request $request, $id, CreatePost $createPost)
    {
        try {
            $post = Post::find($id);
            Gate::authorize('modify', $post);
            $updatedPost = $createPost->handle($request, $id);
            return new PostResource($updatedPost);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }

    }

    public function destroy(Request $request, Post $post)
    {
        try {
            Gate::authorize('modify', $post);
            $post->delete();

            return response()->json([
                'message' => 'Post has been deleted',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    public function topPosts(Request $request)
    {
        $posts = Post::query()
            ->with('author', 'media', 'comments.user')
            ->orderBy(DB::raw('comment_count + like_count'), 'desc')
            ->where('comment_count', '>', 0)
            ->where('like_count', '>', 0)
            ->paginate(10);

        return PostResource::collection($posts);
    }

    public function storeComments(Request $request, CreateComment $createComment)
    {
        try {
            $createComment->handle($request);
            return response()->json([
                'error'     => 0,
                'message'   => 'Comment has been sent.',
            ], 201);
        } Catch (\Exception $e) {
            return response()->json([
                'error'     => 0,
                'message'   => 'Failed to send comment.',
                'response' => $e->getMessage(),
            ]);
        }

    }

    public function storeLike(Request $request)
    {
        $liked = PostLiked::query()->where('post_id', $request->post_id)->where('user_id', auth()->id())->first();

        if(!$liked) {
            PostLiked::query()->create([
                'post_id' => $request->post_id,
                'user_id' => $request->user()->id
            ]);

            $post = Post::query()->find($request->post_id);
            if($post->user_id != auth()->id()) {
                Notification::send($post?->author, new NewLike($postLiked, User::find(auth()->id())));
            }
        }

        return response()->json([
            'error'     => 0,
            'message'   => 'Post has been liked',
        ], 201);
    }

    public function unlikePost(Request $request)
    {
        PostLiked::query()->where('post_id', $request->post_id)->where('user_id', auth()->id())->delete();

        return response()->json([
            'error'     => 0,
            'message'   => 'Post has been unliked',
        ], 201);
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

            return response()->json([
                'error'     => 0,
                'message'   => 'Comment has been liked',
            ]);
        }
    }

    public function unlikeComment(Request $request)
    {
        CommentLiked::query()->where('comment_id', $request->comment_id)->where('user_id', auth()->id())->delete();

        return response()->json([
            'error'     => 0,
            'message'   => 'Comment has been unliked',
        ], 201);
    }
}

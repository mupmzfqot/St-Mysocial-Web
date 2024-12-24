<?php

namespace App\Http\Controllers\API;

use App\Actions\Posts\CreatePost;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLiked;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function storeComments(Request $request)
    {
        Comment::query()->create([
            'post_id' => $request->post_id,
            'message' => $request->message,
            'user_id' => $request->user()->id
        ]);

        return response()->json([
            'error'     => 0,
            'message'   => 'Comment has been sent.',
        ], 201);

    }

    public function storeLike(Request $request)
    {
        $liked = PostLiked::query()->where('post_id', $request->post_id)->where('user_id', auth()->id())->first();

        if(!$liked) {
            PostLiked::query()->create([
                'post_id' => $request->post_id,
                'user_id' => $request->user()->id
            ]);
        }

        return response()->json([
            'error'     => 0,
            'message'   => 'Post has been liked',
        ], 201);
    }
}

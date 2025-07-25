<?php

namespace App\Http\Controllers\API;

use App\Actions\Posts\CreateComment;
use App\Actions\Posts\CreatePostAPI;
use App\Actions\Posts\Repost;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
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
use App\Actions\Posts\UpdatePost;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Services\PostCacheService;

class PostController extends Controller
{
    protected $postCacheService;
    
    public function __construct(PostCacheService $postCacheService)
    {
        $this->postCacheService = $postCacheService;
    }

    public function index(Request $request)
    {
        try {
            $type = $request->query('type');
            if($request->user()->hasRole('public_user')) {
                $type = 'public';
            }
            $searchTerm = $request->query('search');
            $perPage = $request->query('per_page', 20);
            
            $posts = $this->postCacheService->getCachedPosts(
                $type, 
                $searchTerm, 
                $perPage, 
                $request->getQueryString()
            );

            return response()->json([
                'error' => 0,
                'data' => PostResource::collection($posts->load('repost')),
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'from' => $posts->firstItem(),
                    'to' => $posts->lastItem(),
                    'has_more_pages' => $posts->hasMorePages(),
                    'next_page_url' => $posts->nextPageUrl(),
                    'prev_page_url' => $posts->previousPageUrl(),
                    'first_page_url' => $posts->url(1),
                    'last_page_url' => $posts->url($posts->lastPage()),
                    'path' => $posts->path(),
                    'links' => $posts->linkCollection()->toArray(),
                ],
                'meta' => [
                    'type' => $type,
                    'search_term' => $searchTerm,
                    'per_page' => (int) $perPage,
                    'query_string' => $request->getQueryString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json([
                'error'     => 1,
                'message'   => 'Post not found',
            ], 404);
        }

        return response()->json([
            'error' => 0,
            'data' => new PostResource($post->load('comments', 'repost'))
        ]);
    }

    public function store(Request $request, CreatePostAPI $createPost)
    {
        $created = $createPost->handle($request);

        if ($created instanceof Post) {
            // Invalidate cache for the post type (double safety with observer)
            $this->postCacheService->clearCache($created->type);
            
            return response()->json([
                'error' => 0,
                'data' => new PostResource($created)
            ]);
        }

        return response()->json([
            'error' => 1,
            'message' => $created
        ], 400);
    }

    public function update(Request $request, $id, UpdatePost $updatePost)
    {
        try {
            $post = Post::find($id);
            Gate::authorize('modify', $post);
            $updatedPost = $updatePost->handle($request, $post);
            
            // Invalidate cache for the post type (double safety with observer)
            $this->postCacheService->clearCache($updatedPost->type);
            
            return response()->json([
                'error' => 0,
                'data' => new PostResource($updatedPost->load('repost'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage(),
            ], 403);
        }

    }

    public function destroy(Request $request, Post $post)
    {
        try {
            Gate::authorize('modify', $post);
            $postType = $post->type; // Store type before deletion
            $post->delete();

            // Invalidate cache for the post type (double safety with observer)
            $this->postCacheService->clearCache($postType);

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

        return response()->json([
            'error' => 0,
            'data' => PostResource::collection($posts->load('repost')),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
                'has_more_pages' => $posts->hasMorePages(),
                'next_page_url' => $posts->nextPageUrl(),
                'prev_page_url' => $posts->previousPageUrl(),
                'first_page_url' => $posts->url(1),
                'last_page_url' => $posts->url($posts->lastPage()),
                'path' => $posts->path(),
                'links' => $posts->linkCollection()->toArray(),
            ],
            'meta' => [
                'type' => 'top_posts',
                'per_page' => 10,
                'query_string' => $request->getQueryString(),
            ]
        ]);
    }

    public function storeComments(Request $request, CreateComment $createComment)
    {
        try {
            $comment = $createComment->handle($request);
            
            // Invalidate cache for specific post (double safety with observer)
            $this->postCacheService->invalidatePostCache($comment->post_id);
            
            return response()->json([
                'error'     => 0,
                'message'   => 'Comment has been sent.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error'     => 1,
                'message'   => 'Failed to send comment.',
                'response' => $e->getMessage(),
            ]);
        }
    }

    public function getComments(Request $request, $postId)
    {
        $comments = Comment::query()->where('post_id', $postId)
            ->with('media')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'error' => 0,
            'data'  => CommentResource::collection($comments)
        ]);
    }

    public function likedBy(Request $request, $id)
    {
        $user = User::whereHas('likes', function ($query) use ($id) {
                    $query->where('post_id', $id);
                })->get();

        $code = !$user ? '404' : 200;

        return response()->json([
            'error' => 0,
            'data' => UserResource::collection($user)
        ], $code);
    }

    public function storeLike(Request $request)
    {
        $liked = PostLiked::query()
            ->where('post_id', $request->post_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if(!$liked) {
            $postLiked = PostLiked::query()->create([
                'post_id' => $request->post_id,
                'user_id' => $request->user()->id
            ]);

            $post = Post::query()->find($request->post_id);
            if($post->user_id != auth()->id()) {
                Notification::send($post?->author, new NewLike($postLiked, User::find(auth()->id())));
            }

            // Invalidate cache for specific post (double safety with observer)
            $this->postCacheService->invalidatePostCache($request->post_id);
        }

        return response()->json([
            'error'     => 0,
            'message'   => 'Post has been liked',
        ], 201);
    }

    public function unlikePost(Request $request)
    {
        PostLiked::query()
            ->where('post_id', $request->post_id)
            ->where('user_id', auth()->id())
            ->delete();

        // Invalidate cache for specific post (double safety with observer)
        $this->postCacheService->invalidatePostCache($request->post_id);

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

            // Invalidate cache for the post (double safety with observer)
            $this->postCacheService->invalidatePostCache($comment->post_id);

            return response()->json([
                'error'     => 0,
                'message'   => 'Comment has been liked',
            ]);
        }
    }

    public function unlikeComment(Request $request)
    {
        $comment = CommentLiked::query()->where('comment_id', $request->comment_id)->where('user_id', auth()->id())->first();
        
        if ($comment) {
            $commentModel = Comment::query()->find($request->comment_id);
            CommentLiked::query()->where('comment_id', $request->comment_id)->where('user_id', auth()->id())->delete();

            // Invalidate cache for the post (double safety with observer)
            $this->postCacheService->invalidatePostCache($commentModel->post_id);
        }

        return response()->json([
            'error'     => 0,
            'message'   => 'Comment has been unliked',
        ], 201);
    }

    public function repost(Request $request, Repost $repost)
    {
        try {
            $repostResult = $repost->handle($request->user()->id, $request);
            return response()->json([
                'error'     => 0,
                'data'      => $repostResult,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'     => 1,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function getUserPosts(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id'
            ]);

            $perPage = $request->query('per_page', 20);
            $query = Post::query()
                ->orderBy('created_at', 'desc')
                ->where('user_id', $request->user_id)
                ->with(['author', 'media']);

            if($request->user()->hasRole('public_user')) {
                $query->where('type', 'public');
            }

            $posts = $query->paginate($perPage)
                ->withQueryString();

            return response()->json([
                'error' => 0,
                'data'  => PostResource::collection($posts->load('repost')),
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'from' => $posts->firstItem(),
                    'to' => $posts->lastItem(),
                    'has_more_pages' => $posts->hasMorePages(),
                    'next_page_url' => $posts->nextPageUrl(),
                    'prev_page_url' => $posts->previousPageUrl(),
                    'first_page_url' => $posts->url(1),
                    'last_page_url' => $posts->url($posts->lastPage()),
                    'path' => $posts->path(),
                    'links' => $posts->linkCollection()->toArray(),
                ],
                'meta' => [
                    'user_id' => $request->user_id,
                    'per_page' => (int) $perPage,
                    'query_string' => $request->getQueryString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function deleteComment(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $comment = Comment::query()->find($id);

            if(!$comment) {
                return response()->json([
                    'error' => 1,
                    'message' => 'Comment not found',
                ], 404);
            }
            if($request->user()->id === $comment->user_id) {

                $comment->getMedia()->each(function ($media) {
                    $media->delete();
                });
                $comment->delete();
                DB::commit();
                
                // Invalidate cache for specific post (double safety with observer)
                $this->postCacheService->invalidatePostCache($comment->post_id);
                
                return response()->json([
                    'error' => 0,
                    'message' => 'Comment has been deleted'
                ], 202);
            }

            return response()->json([
                'error' => 0,
                'message' => "You don't have permission to delete this comment"
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage(),
            ]);
        }

    }

    public function deleteMediaByUrl(Request $request)
    {
        try {
            $request->validate([
                'media_url' => 'required|string'
            ]);

            $parseUrl = parse_url($request->media_url);
            $path = $parseUrl['path'];
            $segments = explode('/', $path);

            if (count($segments) < 5) {
                throw new \Exception('Invalid media URL format');
            }

            $media_id = $segments[3];
            $filename = $segments[4];

            $media = Media::query()
                ->where('id', $media_id)
                ->where('file_name', $filename)
                ->where('model_type', Post::class)
                ->first();

            if (!$media) {
                return response()->json([
                    'error' => 1,
                    'message' => 'Media not found',
                ], 404);
            }

            $post = Post::find($media->model_id);
            Gate::authorize('modify', $post);

            $media->delete();

            return response()->json([
                'error' => 0,
                'message' => 'Media has been deleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

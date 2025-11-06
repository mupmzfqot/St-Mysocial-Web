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
use App\Services\PostCacheService;
use App\Traits\HasBlockedUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class HomeController extends Controller
{
    use HasBlockedUsers;
    
    protected $postCacheService;
    
    public function __construct(PostCacheService $postCacheService)
    {
        $this->postCacheService = $postCacheService;
    }

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

    public function showMyPosts()
    {
        return Inertia::render('Homepage/MyPost', [
            'title' => 'My Posts',
            'description' => 'Posts created by you',
            'requestUrl' => route('user-post.my-posts'),
        ]);
    }

    public function showPost($id)
    {
        if(auth()->user()->hasRole('admin')) {
            return redirect()->route('post.show', $id);
        }

        $blockedUserIds = $this->getBlockedUserIds(auth()->id());
        
        $post = Post::query()
            ->excludeBlocked(auth()->id())
            ->with([
                'author', 
                'media', 
                'tags', 
                'comments' => function($query) use ($blockedUserIds) {
                    $query->whereNull('deleted_at')
                        ->excludeBlocked(auth()->id())
                        ->with('user', 'media');
                }, 
                'repost' => function($query) use ($blockedUserIds) {
                    if (!empty($blockedUserIds)) {
                        $query->whereNotIn('user_id', $blockedUserIds);
                    }
                },
                'repost.author', 
                'repost.media'
            ])
            ->orderBy('created_at', 'desc')
            ->published()
            ->where('id', $id)
            ->first();

        if (!$post) {
            return redirect()->route('homepage')->with('error', 'Post not found or not published.');
        }
        
        // Check if post is from blocked user
        if (in_array($post->user_id, $blockedUserIds)) {
            return redirect()->route('homepage')->with('error', 'Post not found.');
        }

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

        $blockedUserIds = $this->getBlockedUserIds(auth()->id());
        
        $post = Post::query()
            ->excludeBlocked(auth()->id())
            ->with([
                'author', 
                'media', 
                'comments' => function($query) use ($blockedUserIds) {
                    $query->whereNull('deleted_at')
                        ->excludeBlocked(auth()->id())
                        ->with('user');
                }, 
                'tags', 
                'repost' => function($query) use ($blockedUserIds) {
                    if (!empty($blockedUserIds)) {
                        $query->whereNotIn('user_id', $blockedUserIds);
                    }
                },
                'repost.author', 
                'repost.media', 
                'repost.tags'
            ])
            ->where('id', $id)
            ->first();

        if (!$post) {
            return redirect()->route('homepage')->with('error', 'Post not found.');
        }
        
        // Check if post is from blocked user
        if (in_array($post->user_id, $blockedUserIds)) {
            return redirect()->route('homepage')->with('error', 'Post not found.');
        }

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

        $post = $createPost->handle($request);
        
        // Invalidate cache for the post type
        if ($post instanceof Post) {
            $this->postCacheService->clearCache($post->type);
        }
        
        //return redirect()->route('homepage');
    }

    public function storeComment(Request $request, CreateComment $createComment)
    {
        try {
            $comment = $createComment->handle($request);
            
            // Invalidate cache for the post (double safety with observer)
            $this->postCacheService->invalidatePostCache($comment->post_id);
        } catch (\Exception $e) {
            // Handle error if needed
            logger($e->getMessage());
        }
    }

    public function storeLike(Request $request)
    {
        $blockedUserIds = $this->getBlockedUserIds(auth()->id());
        
        $liked = PostLiked::query()
            ->where('post_id', $request->post_id)
            ->where('user_id', auth()->id())
            ->first();

        if(!$liked) {
            $post = Post::query()->find($request->post_id);
            
            // Check if post is from blocked user
            if ($post && in_array($post->user_id, $blockedUserIds)) {
                return response()->json([
                    'error' => 'You cannot like this post'
                ], 403);
            }
            
            $postLiked = PostLiked::query()->create([
                'post_id' => $request->post_id,
                'user_id' => auth()->id()
            ]);

            if($post && $post->user_id != auth()->id()) {
                Notification::send($post?->author, new NewLike($postLiked, User::find(auth()->id())));
            }
            
            // Invalidate cache for the post (double safety with observer)
            $this->postCacheService->invalidatePostCache($request->post_id);
        }
    }

    public function storeCommentLike(Request $request)
    {
        $blockedUserIds = $this->getBlockedUserIds(auth()->id());
        
        $liked = CommentLiked::query()
            ->where('comment_id', $request->comment_id)
            ->where('user_id', auth()->id())
            ->first();

        if(!$liked) {
            $comment = Comment::query()->find($request->comment_id);
            
            // Check if comment is from blocked user
            if ($comment && in_array($comment->user_id, $blockedUserIds)) {
                return response()->json([
                    'error' => 'You cannot like this comment'
                ], 403);
            }
            
            $commentLiked = CommentLiked::query()->create([
                'comment_id' => $request->comment_id,
                'user_id' => auth()->id()
            ]);

            if($comment && $comment->user_id != auth()->id()) {
                Notification::send($comment?->user, new NewCommentLike($comment, User::find(auth()->id())));
            }
            
            // Invalidate cache for the post (double safety with observer)
            $this->postCacheService->invalidatePostCache($comment->post_id);
        }
    }

    public function unlike(Request $request)
    {
        PostLiked::query()
            ->where('post_id', $request->post_id)
            ->where('user_id', auth()->id())
            ->delete();
            
        // Invalidate cache for the post (double safety with observer)
        $this->postCacheService->invalidatePostCache($request->post_id);
    }

    public function unlikeComment(Request $request)
    {
        $comment = Comment::query()->find($request->comment_id);
        CommentLiked::query()
            ->where('comment_id', $request->comment_id)
            ->where('user_id', auth()->id())
            ->delete();
            
        // Invalidate cache for the post (double safety with observer)
        if ($comment) {
            $this->postCacheService->invalidatePostCache($comment->post_id);
        }
    }

    public function deleteComment(Request $request)
    {
        DB::beginTransaction();
        try {
            $comment = Comment::query()->find($request->content_id);
            if ($comment) {
                $comment->likes()->delete();
                $comment->delete();
                DB::commit();
                
                // Invalidate cache for the post (double safety with observer)
                $this->postCacheService->invalidatePostCache($comment->post_id);
            }
        } catch (\Exception $e) {
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
        $post = Post::query()
            ->where('user_id', auth()->id())
            ->whereId($request->content_id)
            ->first();
            
        if ($post) {
            $postType = $post->type; // Store type before deletion
            
            $post->comments()->delete();
            $post->likes()->delete();
            $post->delete();
            
            // Invalidate cache for the post type
            $this->postCacheService->clearCache($postType);
        }
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

<?php

namespace App\Http\Controllers;

use App\Actions\Posts\CreatePost;
use App\Actions\Posts\Repost;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\Report;
use App\Models\User;
use App\Services\PostCacheService;
use App\Traits\HasBlockedUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostController extends Controller
{
    use HasBlockedUsers;
    
    protected $postCacheService;
    
    public function __construct(PostCacheService $postCacheService)
    {
        $this->postCacheService = $postCacheService;
    }

    /**
     * Add is_reported property to post(s)
     */
    protected function addIsReportedToPosts($posts, $userId = null)
    {
        if (!$userId) {
            $userId = auth()->id();
        }

        if (!$userId) {
            return $posts;
        }

        // If it's a paginated collection
        if (method_exists($posts, 'getCollection')) {
            $collection = $posts->getCollection();
            $postIds = $collection->pluck('id')->toArray();
            
            // Get all reported post IDs by current user
            $reportedPostIds = Report::where('reporter_id', $userId)
                ->where('reportable_type', Post::class)
                ->whereIn('reportable_id', $postIds)
                ->pluck('reportable_id')
                ->toArray();
            
            // Add is_reported to each post
            $collection->transform(function ($post) use ($reportedPostIds) {
                $post->is_reported = in_array($post->id, $reportedPostIds);
                
                // Also check repost if exists
                if ($post->repost) {
                    $post->repost->is_reported = in_array($post->repost->id, $reportedPostIds);
                }
                
                return $post;
            });
            
            $posts->setCollection($collection);
        } 
        // If it's a single post
        elseif ($posts instanceof Post) {
            $isReported = Report::where('reporter_id', $userId)
                ->where('reportable_type', Post::class)
                ->where('reportable_id', $posts->id)
                ->exists();
            
            $posts->is_reported = $isReported;
            
            // Also check repost if exists
            if ($posts->repost) {
                $isRepostReported = Report::where('reporter_id', $userId)
                    ->where('reportable_type', Post::class)
                    ->where('reportable_id', $posts->repost->id)
                    ->exists();
                
                $posts->repost->is_reported = $isRepostReported;
            }
        }
        // If it's a collection
        elseif (is_iterable($posts)) {
            $postIds = collect($posts)->pluck('id')->toArray();
            
            $reportedPostIds = Report::where('reporter_id', $userId)
                ->where('reportable_type', Post::class)
                ->whereIn('reportable_id', $postIds)
                ->pluck('reportable_id')
                ->toArray();
            
            foreach ($posts as $post) {
                $post->is_reported = in_array($post->id, $reportedPostIds);
                
                if ($post->repost) {
                    $post->repost->is_reported = in_array($post->repost->id, $reportedPostIds);
                }
            }
        }

        return $posts;
    }

    public function get(Request $request)
    {
        try {
            $blockedUserIds = $this->getBlockedUserIds(auth()->id());

            $query = Post::query()
                ->excludeBlocked(auth()->id())
                ->with([
                    'author',
                    'media',
                    'comments' => function($query) use ($blockedUserIds) {
                        $query->whereNull('deleted_at')
                            ->excludeBlocked(auth()->id())
                            ->with('user', 'media');
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
                ->whereHas('author.roles', function ($query) {
                    $query->whereIn('name', ['admin', 'user']);
                })
                ->orderBy('created_at', 'desc')
                ->where(function ($query) {
                    // Users can see their own posts or published posts from others
                    $query->where('user_id', auth()->id())
                          ->orWhere('published', true);
                });


            if ($request->has('type')) {
                $request->validate(['type' => 'required|in:st,public']);
                $type = auth()->user()->hasRole('public_user') ? 'public' : $request->input('type');
                $query->where('type', $type);
            }

            $posts = $query->simplePaginate(30)
                ->withQueryString();

            $posts = $this->addIsReportedToPosts($posts);

            return response()->json($posts);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'An unexpected error occurred while retrieving posts.',
            ], 300);
        }
    }

    public function postById($id)
    {
        $blockedUserIds = $this->getBlockedUserIds(auth()->id());
        
        $post = Post::query()
            ->excludeBlocked(auth()->id())
            ->with([
                'author', 
                'media', 
                'comments' => function($query) use ($blockedUserIds) {
                    $query->whereNull('deleted_at')
                        ->excludeBlocked(auth()->id())
                        ->with('user', 'media');
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
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->where('id', $id)
            ->first();
            
        // Authorization check - ensure user can view this post
        if (!$post || ($post->user_id !== auth()->id() && !$post->published)) {
            return response()->json([
                'error' => 'You do not have permission to view this post'
            ], 403);
        }
        
        // Check if post is from blocked user
        if (in_array($post->user_id, $blockedUserIds)) {
            return response()->json([
                'error' => 'Post not found'
            ], 404);
        }

        $post = $this->addIsReportedToPosts($post);

        return response()->json($post);
    }

    public function index(Request $request)
    {
        $searchTerm = $request->query('search');
        $blockedUserIds = $this->getBlockedUserIds(auth()->id());
        
        $posts = Post::query()
            ->excludeBlocked(auth()->id())
            ->when($searchTerm, function ($query, $search) {
                $query->where('post', 'like', '%' . $search . '%');
            })
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->where(function ($query) {
                // Users can see their own posts or published posts from others
                $query->where('user_id', auth()->id())
                      ->orWhere('published', true);
            })
            ->with(['author', 'media'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Posts/Index', compact('posts', 'searchTerm'));
    }

    public function create()
    {
        $defaultType = 'st';
        $title = 'Create New Post';
        $stUsers = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'user');
        })
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get();
        return Inertia::render('Posts/Form', compact('defaultType', 'stUsers', 'title'));
    }

    public function store(Request $request, CreatePost $createPost)
    {
        try {
            $post = $createPost->handle($request);
            if (is_string($post)) {
                return redirect()->back()->withErrors(['error' => $post]);
            }

            // Invalidate cache for the post type
            $this->postCacheService->clearCache($post->type);

            $message = 'Post successfully created!';
            if ($request->type === 'public' && !auth()->user()->hasRole('admin')) {
                $message = 'Your post will be available after admin approval.';
                return redirect()->back()->with('success', $message);
            }

            if (auth()->user()->hasRole('admin')) {
                return redirect()->route('post.index')->with('success', $message);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $post = Post::query()->find($id);
            
            // Authorization check - ensure user owns the post
            if (!$post || $post->user_id !== auth()->id()) {
                return redirect()->back()->withErrors(['error' => 'You do not have permission to update this post.']);
            }
            
            if ($request->post('files')) {
                $requestMediaId = collect($request->post('files'))->map(function ($file) {
                    return $file['id'];
                })->toArray();
                $existingMediaId = $post->getMedia('post_media')->pluck('id')->toArray();
                if (count($existingMediaId) > count($requestMediaId)) {
                    Media::whereIn('id', array_diff($existingMediaId, $requestMediaId))->delete();
                }
            }

            $post->post = $request->post('content');
            $post->type = $request->type;
            $post->update();

            if (!empty($request->userTags)) {
                PostTag::query()->where('post_id', $post->id)->delete();
                foreach ($request->userTags as $tag) {
                    PostTag::query()->create([
                        'post_id' => $post->id,
                        'user_id'  => $tag,
                        'name'     => User::query()->find($tag)?->name
                    ]);
                }
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $post->addMedia($file)->toMediaCollection('post_media');
                }
            }

            // Invalidate cache for the post type
            $this->postCacheService->clearCache($post->type);

            $message = 'Post successfully updated!';
            if ($request->type === 'public' && !auth()->user()->hasRole('admin')) {
                $message = 'Your post will be available after admin approval.';
                return redirect()->route('home')->with('success', $message);
            }

            if (auth()->user()->hasRole('admin')) {
                return redirect()->route('post-moderation.index-st')->with('success', $message);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function share(Request $request, Repost $repost)
    {
        try {
            $blockedUserIds = $this->getBlockedUserIds(auth()->id());
            
            // Authorization check - ensure user can repost this post
            $post = Post::find($request->post_id);
            if (!$post || ($post->user_id !== auth()->id() && !$post->published)) {
                return response()->json([
                    'error' => 'You do not have permission to repost this post'
                ], 403);
            }
            
            // Check if post is from blocked user
            if (in_array($post->user_id, $blockedUserIds)) {
                return response()->json([
                    'error' => 'You cannot repost this post'
                ], 403);
            }
            
            $repostResult = $repost->handle(auth()->id(), $request);
            
            // Invalidate cache for the post type if repost is successful
            if ($repostResult && $repostResult->resource) {
                $this->postCacheService->clearCache($repostResult->resource->type);
            }
            
            return response()->json($repostResult);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        $blockedUserIds = $this->getBlockedUserIds(auth()->id());
        
        $post = Post::query()
            ->excludeBlocked(auth()->id())
            ->with([
                'author', 
                'media', 
                'comments' => function($query) use ($blockedUserIds) {
                    $query->whereNull('deleted_at')
                        ->excludeBlocked(auth()->id())
                        ->with('user', 'media');
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
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->first();

        if (!$post) {
            return redirect()->route('post.index')->with('error', 'Post not found.');
        }
        
        // Check if post is from blocked user
        if (in_array($post->user_id, $blockedUserIds)) {
            return redirect()->route('post.index')->with('error', 'Post not found.');
        }
        
        // Authorization check - ensure user can view this post
        if ($post->user_id !== auth()->id() && !$post->published) {
            return redirect()->route('post.index')->with('error', 'You do not have permission to view this post.');
        }

        $post = $this->addIsReportedToPosts($post);

        return Inertia::render('Posts/Show', compact('post'));
    }

    public function edit($id)
    {
        $post = Post::query()
            ->with('author', 'media', 'comments.user', 'tags', 'repost.author', 'repost.media', 'repost.tags')
            ->where('id', $id)
            ->first();
            
        // Authorization check - ensure user owns the post
        if (!$post || $post->user_id !== auth()->id()) {
            return redirect()->route('post.index')->withErrors(['error' => 'You do not have permission to edit this post.']);
        }
        
        $defaultType = 'st';
        $title = 'Edit Post';
        $stUsers = User::query()->select('id', 'name')->whereHas('roles', function ($query) {
            $query->where('name', 'user');
        })
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get();

        $post = $this->addIsReportedToPosts($post);

        return Inertia::render('Posts/Form', compact('post', 'defaultType', 'stUsers', 'title'));
    }

    public function getTopPost(Request $request)
    {
        $blockedUserIds = $this->getBlockedUserIds(auth()->id());
        
        $cacheKey = 'top_posts_' . md5(json_encode($request->all()));
        $posts = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request, $blockedUserIds) {
            $query = Post::query()
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
                ->whereHas('author.roles', function ($query) {
                    $query->whereIn('name', ['admin', 'user']);
                })
                ->where('comment_count', '>', 0)
                ->where('like_count', '>', 0)
                ->orderBy(DB::raw('comment_count + like_count'), 'desc')
                ->where(function ($query) {
                    // Users can see their own posts or published posts from others
                    $query->where('user_id', auth()->id())
                          ->orWhere('published', true);
                });

            return $query->simplePaginate(30)->withQueryString();
        });

        $posts = $this->addIsReportedToPosts($posts);

        return response()->json($posts);
    }


    public function getLikedPost(Request $request)
    {
        $blockedUserIds = $this->getBlockedUserIds(auth()->id());
        
        $posts = Post::query()
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
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->orderBy('created_at', 'desc')
            ->whereHas('likes', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->where(function ($query) {
                // Users can see their own posts or published posts from others
                $query->where('user_id', auth()->id())
                      ->orWhere('published', true);
            })
            ->simplePaginate(30);

        $posts = $this->addIsReportedToPosts($posts);

        return response()->json($posts);
    }

    public function getMyPosts(Request $request)
    {
        $posts = Post::query()
            ->with('author', 'media', 'comments.user', 'tags', 'repost.author', 'repost.media', 'repost.tags')
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->orderBy('created_at', 'desc')
            ->where('user_id', auth()->id()) // Only show user's own posts
            ->simplePaginate(30);

        $posts = $this->addIsReportedToPosts($posts);

        return response()->json($posts);
    }

    public function getRecentPost(Request $request)
    {
        $posts = Post::query()
            ->with('author', 'media', 'comments.user', 'tags', 'repost.author', 'repost.media', 'repost.tags')
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->orderBy('created_at', 'desc')
            ->where('user_id', auth()->id()) // Only show user's own posts
            ->simplePaginate(30);

        $posts = $this->addIsReportedToPosts($posts);

        return response()->json($posts);
    }

    public function getTagPost(Request $request)
    {
        $user_id = $request->user_id;
        $blockedUserIds = $this->getBlockedUserIds(auth()->id());
        
        // Exclude blocked users from tag post results
        if (in_array($user_id, $blockedUserIds)) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'per_page' => 30,
                'total' => 0
            ]);
        }
        
        $posts = Post::query()
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
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->orderBy('created_at', 'desc')
            ->where(function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                      ->orWhereHas('tags', function ($tagQuery) use ($user_id) {
                          $tagQuery->where('user_id', $user_id);
                      });
            })
            ->where(function ($query) use ($user_id) {
                // Users can see their own posts or published posts from others
                $query->where('user_id', $user_id)
                      ->orWhere('published', true);
            })
            ->simplePaginate(30);

        $posts = $this->addIsReportedToPosts($posts);

        return response()->json($posts);
    }

    public function getTaggedUser($postId)
    {
        $post = Post::find($postId);
        
        // Authorization check - ensure user can view tagged users for this post
        if (!$post || ($post->user_id !== auth()->id() && !$post->published)) {
            return response()->json([
                'error' => 'You do not have permission to view tagged users for this post'
            ], 403);
        }
        
        $user = User::query()
            ->whereHas('tags', function ($query) use ($postId) {
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

    public function destroy($id)
    {
        $post = Post::query()->find($id);
        
        // Authorization check - ensure user owns the post OR is admin
        if (!$post || ($post->user_id !== auth()->id() && !auth()->user()->hasRole('admin'))) {
            return redirect()->back()->withErrors(['error' => 'You do not have permission to delete this post.']);
        }
        
        $postType = $post->type; // Store type before deletion
        
        $post->comments()?->delete();
        $post->likes()?->delete();
        $post->delete();
        
        // Invalidate cache for the post type
        $this->postCacheService->clearCache($postType);

        return redirect()->back()->with('success', 'Post has been deleted successfully.');
    }
}

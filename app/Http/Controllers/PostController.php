<?php

namespace App\Http\Controllers;

use App\Actions\Posts\CreatePost;
use App\Actions\Posts\Repost;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\User;
use App\Services\PostCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostController extends Controller
{
    protected $postCacheService;
    
    public function __construct(PostCacheService $postCacheService)
    {
        $this->postCacheService = $postCacheService;
    }

    public function get(Request $request)
    {
        try {

            $query = Post::query()
                ->with(
                    'author',
                    'media',
                    'comments.user',
                    'comments.media',
                    'tags',
                    'repost.author',
                    'repost.media',
                    'repost.tags'
                )
                ->whereHas('author.roles', function ($query) {
                    $query->whereIn('name', ['admin', 'user']);
                })
                ->orderBy('created_at', 'desc')
                ->published();


            if ($request->has('type')) {
                $request->validate(['type' => 'required|in:st,public']);
                $type = auth()->user()->hasRole('public_user') ? 'public' : $request->input('type');
                $query->where('type', $type);
            }

            $posts = $query->simplePaginate(30)
                ->withQueryString();

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
        $post = Post::query()
            ->with('author', 'media', 'comments.user', 'comments.media', 'tags', 'repost.author', 'repost.media', 'repost.tags')
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->orderBy('created_at', 'desc')
            ->published()
            ->where('id', $id)
            ->first();

        return response()->json($post);
    }

    public function index(Request $request)
    {
        $searchTerm = $request->query('search');
        $posts = Post::query()
            ->when($searchTerm, function ($query, $search) {
                $query->where('post', 'like', '%' . $search . '%');
            })
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->published()
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
        $post = Post::query()
            ->with('author', 'media', 'comments.user', 'comments.media', 'tags', 'repost.author', 'repost.media', 'repost.tags')
            ->where('id', $id)
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->published()
            ->first();

        if (!$post) {
            return redirect()->route('post.index')->with('error', 'Post not found.');
        }

        return Inertia::render('Posts/Show', compact('post'));
    }

    public function edit($id)
    {
        $defaultType = 'st';
        $title = 'Edit Post';
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

        return Inertia::render('Posts/Form', compact('post', 'defaultType', 'stUsers', 'title'));
    }

    public function getTopPost(Request $request)
    {
        $cacheKey = 'top_posts_' . md5(json_encode($request->all()));
        $posts = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            $query = Post::query()
                ->with('author', 'media', 'comments.user', 'tags', 'repost.author', 'repost.media', 'repost.tags')
                ->whereHas('author.roles', function ($query) {
                    $query->whereIn('name', ['admin', 'user']);
                })
                ->where('comment_count', '>', 0)
                ->where('like_count', '>', 0)
                ->orderBy(DB::raw('comment_count + like_count'), 'desc')
                ->published();

            return $query->simplePaginate(30)->withQueryString();
        });

        return response()->json($posts);
    }


    public function getLikedPost(Request $request)
    {
        $posts = Post::query()
            ->with('author', 'media', 'comments.user', 'tags', 'repost.author', 'repost.media', 'repost.tags')
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->orderBy('created_at', 'desc')
            ->whereHas('likes', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->published()
            ->simplePaginate(30);

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
            ->where('user_id', auth()->id())
            ->simplePaginate(30);

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
            ->where('user_id', auth()->id())
            ->published()
            ->simplePaginate(30);

        return response()->json($posts);
    }

    public function getTagPost(Request $request)
    {
        $user_id = $request->user_id;
        $posts = Post::query()
            ->with('author', 'media', 'comments.user', 'tags', 'repost.author', 'repost.media', 'repost.tags')
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['admin', 'user']);
            })
            ->orderBy('created_at', 'desc')
            ->where('user_id', $user_id)
            ->orWhereHas('tags', function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->simplePaginate(30);

        return response()->json($posts);
    }

    public function getTaggedUser($postId)
    {
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
            });;

        return response()->json($user);
    }

    public function destroy($id)
    {
        $post = Post::query()->find($id);
        if ($post) {
            $postType = $post->type; // Store type before deletion
            
            $post->comments()?->delete();
            $post->likes()?->delete();
            $post->delete();
            
            // Invalidate cache for the post type
            $this->postCacheService->clearCache($postType);
        }

        redirect()->back();
    }
}

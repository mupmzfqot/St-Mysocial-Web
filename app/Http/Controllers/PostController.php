<?php

namespace App\Http\Controllers;

use App\Actions\Posts\CreatePost;
use App\Actions\Posts\Repost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostController extends Controller
{
    public function get(Request $request)
    {
        try {
            $posts = Post::query()
                ->with('author', 'media', 'comments.user', 'tags', 'repost.author', 'repost.media', 'repost.tags')
                ->orderBy('created_at', 'desc')
                ->published()
                ->where('type', 'st')
                ->paginate(30)
                ->through(function ($post) {
                    $post->load('likes');
                    return $post;
                })
                ->withQueryString();

            return response()->json($posts);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'An unexpected error occurred while retrieving posts.',
                'status' => 500
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $searchTerm = $request->query('search');
        $posts = Post::query()
            ->when($searchTerm, function ($query, $search) {
                $query->where('post', 'like', '%' . $search . '%');
            })
            ->whereHas('author.roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->published()
            ->with(['author', 'media'])
            ->orderBy('created_at','desc')
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
            if(is_string($post)) {
                return redirect()->back()->withErrors(['error' => $post]);
            }

            $message = 'Post successfully created!';
            if($request->type === 'public' && !auth()->user()->hasRole('admin')) {
                $message = 'Your post will be available after admin approval.';
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $post = Post::query()->find($id);
            if($request->post('files')) {
                $requestMediaId = collect($request->post('files'))->map(function ($file) { return $file['id']; })->toArray();
                $existingMediaId = $post->getMedia('post_media')->pluck('id')->toArray();
                if (count($existingMediaId) > count($requestMediaId)) {
                    Media::whereIn('id', array_diff($existingMediaId, $requestMediaId))->delete();
                }
            }

            $post->post = $request->post('content');
            $post->type = $request->type;
            $post->update();

            if($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $post->addMedia($file)->toMediaCollection('post_media');
                }
            }

            return redirect()->back()->with('success', 'Post successfully updated.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }


    }

    public function share(Request $request, Repost $repost)
    {
        try {
            $repostResult = $repost->handle(auth()->id(), $request);
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
            ->with('author', 'media', 'comments.user', 'tags', 'repost.author', 'repost.media', 'repost.tags')
            ->where('id', $id)
            ->first();

        return Inertia::render('Posts/Show', compact('post'));
    }

    public function edit($id)
    {
        $defaultType = 'st';
        $title = 'Edit Post';
        $stUsers = User::query()->whereHas('roles', function ($query) {
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
}

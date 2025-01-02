<?php

namespace App\Http\Controllers;

use App\Actions\Posts\CreatePost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $searchTerm = $request->query('search');
        $posts = Post::query()
            ->when($searchTerm, function ($query, $search) {
                $query->where('post', 'like', '%' . $search . '%');
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
        $stUsers = User::query()->whereHas('roles', function ($query) {
                    $query->where('name', 'user');
                })
                ->where('is_active', true)
                ->whereNotNull('email_verified_at')
                ->get();
        return Inertia::render('Posts/Form', compact('defaultType', 'stUsers'));
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
}

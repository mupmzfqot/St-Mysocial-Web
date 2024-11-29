<?php

namespace App\Http\Controllers;

use App\Actions\Posts\CreatePost;
use App\Models\Post;
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
            ->with(['author'])
            ->orderBy('created_at','desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Posts/Index', compact('posts', 'searchTerm'));
    }

    public function create()
    {
        return Inertia::render('Posts/Form');
    }

    public function store(Request $request, CreatePost $createPost)
    {
        $request->validate([
            'content' => 'required|string',
            'group'   => 'nullable'
        ]);

        $createPost->handle($request);

        return redirect()->route('post.index');
    }
}

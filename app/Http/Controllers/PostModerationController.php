<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostModerationController extends Controller
{
    public function index(Request $request)
    {
        $searchTerm = $request->search;
        $posts = Post::query()
            ->when($searchTerm, function ($query, $search) {
                $query->where('post', 'like', '%' . $search . '%');
            })
            ->with(['author'])
            ->whereHas('userRole', function ($query) {
                $query->where('roles.name', 'public_user');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PostModerations/Index', compact('posts', 'searchTerm'));
    }

    public function updateStatus(Request $request, $id)
    {
        $post = Post::query()->find($id);
        $post->published = $request->is_active;
        $post->update();

        return redirect()->back();
    }
}

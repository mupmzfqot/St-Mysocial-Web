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
            ->whereHas('author.roles', function ($query) {
                $query->whereIn('name', ['user']);
            })
            ->where('type', 'st')
            ->with(['author'])
            ->where('published', false)
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PostModerations/IndexST', compact('posts', 'searchTerm'));
    }
    public function indexST(Request $request)
    {
        $searchTerm = $request->search;
        $posts = Post::query()
            ->when($searchTerm, function ($query, $search) {
                $query->where('post', 'like', '%' . $search . '%');
            })
            ->whereHas('author.roles', function ($query) {
                $query->where('name', 'user');
            })
            ->with(['author', 'media'])
            ->where('type', 'st')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PostModerations/IndexST', compact('posts', 'searchTerm'));
    }


    public function updateStatus(Request $request, $id)
    {
        $post = Post::query()->find($id);
        $post->published = $request->is_active;
        $post->update();

        return redirect()->back();
    }
}

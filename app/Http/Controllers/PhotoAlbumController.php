<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PhotoAlbumController extends Controller
{
    public function index()
    {
        $postId = Post::query()
            ->where('user_id', auth()->id())
            ->pluck('id');

        $media = Media::query()
            ->whereIn('model_id', $postId)
            ->where('model_type', Post::class)
            ->get();

        return Inertia::render('PhotoAlbum/Index', compact('media'));
    }
}

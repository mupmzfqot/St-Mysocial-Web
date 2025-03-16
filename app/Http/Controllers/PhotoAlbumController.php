<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PhotoAlbumController extends Controller
{
    public function index()
    {
        $postId = Post::query()
            ->where('user_id', auth()->id())
            ->pluck('id');

        $medias = Media::query()
            ->whereIn('model_id', $postId)
            ->where('model_type', Post::class)
            ->whereLike('mime_type', 'image/%')
            ->get();

        return Inertia::render('PhotoAlbum/Index', compact('medias'));
    }

    public function videos()
    {
        $postId = Post::query()
            ->where('user_id', auth()->id())
            ->pluck('id');

        $medias = Media::query()
            ->whereIn('model_id', $postId)
            ->where('model_type', Post::class)
            ->whereLike('mime_type', 'video/%')
            ->get();


        return Inertia::render('Homepage/MyVideo', compact('medias'));
    }
}

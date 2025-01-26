<?php

namespace App\Http\Controllers;

use App\Http\Resources\MediaResource;
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
            ->where('mime_type', '!=', 'application/pdf')
            ->get();

        return Inertia::render('PhotoAlbum/Index', compact('medias'));
    }
}

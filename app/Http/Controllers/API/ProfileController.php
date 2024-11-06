<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => new UserResource($request->user())
        ]);
    }

    public function update(Request $request)
    {

    }

    public function updateProfileImage(Request $request, $id)
    {

    }

    public function updateProfileCover(Request $request, $id)
    {

    }

    public function changePassword(Request $request)
    {

    }

    public function getPosts(Request $request)
    {
        $posts = Post::query()
            ->orderBy('created_at', 'desc')
            ->where('user_id', $request->user()->id)
            ->published()
            ->paginate(30);

        return PostResource::collection($posts);
    }

    public function likedPosts(Request $request)
    {
        $posts = Post::query()
            ->orderBy('created_at', 'desc')
            ->whereHas('likes', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->published()
            ->paginate(30);

        return PostResource::collection($posts);
    }
}

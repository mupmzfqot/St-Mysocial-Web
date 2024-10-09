<?php

namespace App\Http\Controllers\API;

use App\Actions\Posts\CreatePost;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(Request $request, CreatePost $createPost)
    {
        $created = $createPost->handle($request);

        if ($created) {
            return response([
                'code' => 201,
                'message' => 'Post created.',
                'data' => $created
            ], 201);
        }

        return response([
            'code' => 403,
            'message' => 'Post not created.',
        ], 403);

    }
}

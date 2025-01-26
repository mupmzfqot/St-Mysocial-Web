<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return response()->json([
             'data' => new UserResource($request->user())
        ]);
    }

    public function searchUser(Request $request)
    {
        $searchTerm = $request->search;
        $users = User::query()
            ->when($searchTerm, function ($query, $searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('username', 'like', '%' . $searchTerm . '%');
            })
            ->exceptId($request->user()->id)
            ->isActive()
            ->orderBy('name', 'asc')
            ->paginate(20);

        return  response()->json([
            'error' => 0,
            'data' => UserResource::collection($users),
        ], 200);
    }

    public function getTeams(Request $request)
    {
        $authId = $request->user()->id;
        $cacheKey = 'team_users_' . $authId;
        $cacheDuration = now()->addMinutes(10);

        return \Cache::remember($cacheKey, $cacheDuration, function() use($authId) {
            $query = User::query()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'user');
                })
                ->whereNotNull('email_verified_at')
                ->isActive();

            $query->where('id', '!=', $authId);

            return response()->json([
                'error' => 0,
                'data' => UserResource::collection($query->get())
            ]);
        });
    }

    public function getMedia(Request $request)
    {
        $user = $request->user()->id;
        $postMedia = Post::where('user_id', $user)->with('media')->get();

        $media = $postMedia?->map(function ($post) {
            $medias = [];
             foreach ($post->media as $item) {
                 $medias[] = [
                     'id' => $item['id'],
                     'name' => $item['name'],
                     'file_name' => $item['file_name'],
                     'mime_type' => $item['mime_type'],
                     'size' => $item['size'],
                     'collection_name' => $item['collection_name'],
                     'url' => $item['original_url'],
                 ];
             }

             return $medias;
        });

        return response()->json([
            'error' => 0,
            'data' => $media
        ]);

    }
}

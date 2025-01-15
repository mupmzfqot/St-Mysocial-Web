<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
            ->whereNot('id', $request->user()->id)
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
                ->where('is_active', true);

            $query->where('id', '!=', $authId);

            return UserResource::collection($query->get());
        });
    }

    public function getMedia(Request $request)
    {
        $user = $request->user()->id;

        $userMedia = User::find($user)->getMedia('*')?->toArray();
        $postMedia = Post::find($user)->getMedia('*')?->toArray();

        $mergedArray = array_merge($userMedia, $postMedia);
        $media = collect($mergedArray)->map(function ($item) {
             return [
                 'id' => $item['id'],
                 'name' => $item['name'],
                 'file_name' => $item['file_name'],
                 'mime_type' => $item['mime_type'],
                 'size' => $item['size'],
                 'collection_name' => $item['collection_name'],
                 'url' => $item['original_url'],
             ];
        });

        return response()->json([
            'error' => 0,
            'data' => $media
        ]);

    }

    public function updateProfileImage(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate(['image' => 'required|image']);
            Media::query()->where('model_id', $request->user()->id)
                ->where('model_type', User::class)
                ->where('collection_name', 'avatar')
                ->delete();

            User::find($request->user()->id)->addMediaFromRequest('image')->toMediaCollection('avatar');

            DB::commit();
            return response()->json(['message' => 'Profile image updated successfully', 'error' => 0]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 1]);
        }

    }

    public function updateProfileCover(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate(['image' => 'required|image']);
            Media::query()->where('model_id', $request->user()->id)
                ->where('model_type', User::class)
                ->where('collection_name', 'cover_image')
                ->delete();

            User::find($request->user()->id)->addMediaFromRequest('image')->toMediaCollection('cover_image');

            DB::commit();
            return response()->json(['message' => 'Profile cover updated successfully', 'error' => 0]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 1]);
        }
    }
}

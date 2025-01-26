<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
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
        try {
            $user = $request->user()->id;
            $post_id = Post::where('user_id', $user)->get()->pluck('id');

            $medias = Media::query()
                ->whereIn('model_id', $post_id)
                ->where('model_type', Post::class)
                ->where('mime_type', '!=', 'application/pdf')
                ->get();

            $groupedMedias = $medias->map(fn ($item) => [
                'id'            => $item->id,
                'filename'      => $item->file_name,
                'preview_url'   => $item->preview_url,
                'original_url'  => $item->original_url,
                'extension'     => $item->extension,
                'mime_type'     => $item->mime_type,
            ])->groupBy(function ($item) {
                if (str_starts_with($item['mime_type'], 'video/')) {
                    return 'video';
                } elseif (str_starts_with($item['mime_type'], 'image/')) {
                    return 'image';
                } elseif ($item['mime_type'] === 'application/pdf') {
                    return 'document';
                }
                return 'other';
            })->map(function ($items, $type) {
                return [
                    'type' => $type,
                    'content' => $items->pluck('original_url')->all(),
                ];
            })->values();

            return response()->json([
                'error' => 0,
                'data' => $groupedMedias
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ]);
        }

    }
}

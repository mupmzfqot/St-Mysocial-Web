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
        $searchTerm = $request->search;
        $cacheKey = 'team_users_' . $authId . '_' . md5($searchTerm);
        $cacheDuration = now()->addMinutes(1);

        return \Cache::remember($cacheKey, $cacheDuration, function() use ($authId, $searchTerm) {
            $query = User::query()
                ->when($searchTerm, function ($query, $searchTerm) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%$searchTerm%")
                            ->orWhere('email', 'like', "%$searchTerm%")
                            ->orWhere('username', 'like', "%$searchTerm%");
                    });
                })
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'user');
                })
                ->whereNotNull('email_verified_at')
                ->isActive()
                ->where('id', '!=', $authId)
                ->get();

            return response()->json([
                'error' => 0,
                'data' => UserResource::collection($query)
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
                ->whereLike('mime_type', 'image/%')
                ->get();

            $groupedMedias = $this->groupMedia($medias, 'image');

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

    public function getVideo(Request $request)
    {
        try {
            $user = $request->user()->id;
            $post_id = Post::where('user_id', $user)->get()->pluck('id');

            $medias = Media::query()
                ->whereIn('model_id', $post_id)
                ->where('model_type', Post::class)
                ->whereLike('mime_type', 'video/%')
                ->get();

            $groupedMedias = $this->groupMedia($medias, 'video');

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

    private function groupMedia($medias, $type)
    {
        return $medias->map(fn ($item) => [
            'id'            => $item->id,
            'filename'      => $item->file_name,
            'preview_url'   => $item->preview_url,
            'original_url'  => $item->original_url,
            'extension'     => $item->extension,
            'mime_type'     => $item->mime_type,
        ])->groupBy(function ($item) use($type) {
            if (str_starts_with($item['mime_type'], "{$type}/")) {
                return $type;
            }
        })->map(function ($items, $type) {
            return [
                'type' => $type,
                'content' => $items->pluck('original_url')->all(),
            ];
        })->values();
    }
}

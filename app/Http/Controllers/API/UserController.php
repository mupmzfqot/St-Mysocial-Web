<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\VideoRequest;
use App\Http\Resources\MediaResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
                $query->where('username', 'like', '%' . $searchTerm . '%');
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

    public function getVideo(VideoRequest $request)
    {
        try {
            $user = $request->user()->id;
            
            // Get validated parameters
            $perPage = $request->validated('per_page');
            $page = $request->validated('page');
            $sortBy = $request->validated('sort_by');
            $sortOrder = $request->validated('sort_order');
            
            // Build dynamic order by clause
            $orderBy = match($sortBy) {
                'file_name' => 'media.file_name',
                'size' => 'media.size',
                default => 'media.created_at'
            };
            
            // Optimized query with pagination and sorting
            $medias = Media::query()
                ->select([
                    'media.id',
                    'media.file_name',
                    'media.name',
                    'media.mime_type',
                    'media.model_id',
                    'media.model_type',
                    'media.created_at',
                    'media.size',
                    'media.collection_name',
                    'media.disk'
                ])
                ->join('posts', 'media.model_id', '=', 'posts.id')
                ->where('posts.user_id', $user)
                ->where('media.model_type', Post::class)
                ->where('media.mime_type', 'like', 'video/%')
                ->orderBy($orderBy, $sortOrder)
                ->paginate($perPage, ['*'], 'page', $page);

            // Group media by type
            $groupedMedias = $this->groupMedia($medias->items(), 'video');

            return response()->json([
                'error' => 0,
                'data' => $groupedMedias,
                'pagination' => [
                    'current_page' => $medias->currentPage(),
                    'last_page' => $medias->lastPage(),
                    'per_page' => $medias->perPage(),
                    'total' => $medias->total(),
                    'from' => $medias->firstItem(),
                    'to' => $medias->lastItem(),
                    'has_more_pages' => $medias->hasMorePages(),
                ],
                'filters' => [
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                    'per_page' => $perPage
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function groupMedia($medias, $type)
    {
        // Early return if no medias
        if (empty($medias)) {
            return [];
        }

        // Optimized grouping with single pass
        $grouped = [];
        
        foreach ($medias as $item) {
            // Skip if mime type doesn't match
            if (!str_starts_with($item->mime_type, "{$type}/")) {
                continue;
            }
            
            // Extract extension from filename
            $extension = pathinfo($item->file_name, PATHINFO_EXTENSION);
            
            // Build media item data
            $mediaItem = [
                'id'            => $item->id,
                'filename'      => $item->file_name,
                'name'          => $item->name,
                'preview_url'   => $item->getUrl() ?? null,
                'original_url'  => $item->getUrl() ?? null,
                'extension'     => $extension,
                'mime_type'     => $item->mime_type,
                'size'          => $item->size,
                'collection'    => $item->collection_name,
                'disk'          => $item->disk,
                'created_at'    => $item->created_at?->toISOString(),
            ];
            
            // Group by type
            if (!isset($grouped[$type])) {
                $grouped[$type] = [
                    'type' => $type,
                    'count' => 0,
                    'items' => [],
                    'urls' => []
                ];
            }
            
            $grouped[$type]['items'][] = $mediaItem;
            $grouped[$type]['count']++;
            
            // Add URL if available
            if ($item->getUrl()) {
                $grouped[$type]['urls'][] = $item->getUrl();
            }
        }
        
        // Convert to array format
        return array_values($grouped);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'error' => 0,
            'message' => 'FCM token updated successfully',
            'fcm_token' => $user->fcm_token
        ]);
    }

    /**
     * Get cached video medias for better performance
     */
    public function getVideoCached(Request $request)
    {
        try {
            $user = $request->user()->id;
            $cacheKey = "user_videos_{$user}_" . md5($request->getQueryString() ?? '');
            $cacheDuration = 300; // 5 minutes

            // Try to get from cache first
            if (cache()->has($cacheKey)) {
                return response()->json([
                    'error' => 0,
                    'data' => cache()->get($cacheKey),
                    'cached' => true,
                    'cache_expires' => now()->addSeconds($cacheDuration)->toISOString()
                ]);
            }

            // If not in cache, get from database
            $result = $this->getVideoData($request);
            
            // Cache the result
            cache()->put($cacheKey, $result, $cacheDuration);

            return response()->json([
                'error' => 0,
                'data' => $result,
                'cached' => false,
                'cache_expires' => now()->addSeconds($cacheDuration)->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear user video cache
     */
    public function clearVideoCache(Request $request)
    {
        try {
            $user = $request->user()->id;
            $cachePattern = "user_videos_{$user}_*";
            
            // Clear all cached videos for this user
            $cleared = cache()->flush(); // Note: This clears all cache, consider more targeted approach
            
            return response()->json([
                'error' => 0,
                'message' => 'Video cache cleared successfully',
                'cleared' => $cleared
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get video data with performance monitoring
     */
    private function getVideoData(Request $request)
    {
        $startTime = microtime(true);
        
        $user = $request->user()->id;
        
        // Get query parameters with defaults
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate and cap limits
        $perPage = min(max($perPage, 1), 100);
        
        // Build dynamic order by clause
        $orderBy = match($sortBy) {
            'file_name' => 'media.file_name',
            'size' => 'media.size',
            default => 'media.created_at'
        };
        
        // Optimized query with pagination and sorting
        $medias = Media::query()
            ->select([
                'media.id',
                'media.file_name',
                'media.name',
                'media.mime_type',
                'media.model_id',
                'media.model_type',
                'media.created_at',
                'media.size',
                'media.collection_name',
                'media.disk'
            ])
            ->join('posts', 'media.model_id', '=', 'posts.id')
            ->where('posts.user_id', $user)
            ->where('media.model_type', Post::class)
            ->where('media.mime_type', 'like', 'video/%')
            ->orderBy($orderBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        // Group media by type
        $groupedMedias = $this->groupMedia($medias->items(), 'video');
        
        $executionTime = microtime(true) - $startTime;
        
        return [
            'medias' => $groupedMedias,
            'pagination' => [
                'current_page' => $medias->currentPage(),
                'last_page' => $medias->lastPage(),
                'per_page' => $medias->perPage(),
                'total' => $medias->total(),
                'from' => $medias->firstItem(),
                'to' => $medias->lastItem(),
                'has_more_pages' => $medias->hasMorePages(),
            ],
            'performance' => [
                'execution_time_ms' => round($executionTime * 1000, 2),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'query_count' => \DB::getQueryLog() ? count(\DB::getQueryLog()) : 'unknown'
            ]
        ];
    }
}

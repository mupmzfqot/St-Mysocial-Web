<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProfileController extends Controller
{
    protected $deletionService;

    public function __construct(AccountDeletionService $deletionService)
    {
        $this->deletionService = $deletionService;
    }
    public function index(Request $request)
    {
        return response()->json([
            'data' => new UserResource($request->user())
        ]);
    }

    public function get(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $user = User::query()->where('id', $request->user_id)
                ->isActive()
                ->first();

            if(!$user) {
                Throw new \Exception('User not found');
            }
            return response()->json([
                'error' => 0,
                'data' => new UserResource($user)
            ]);
        } Catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' =>   ['required', 'string', 'max:60'],
                'gender' => ['required', 'in:Male,Female'],
            ]);

            $user = User::query()->find($request->user()->id);
            $user->name = $request->name;
            $user->username = $request->username;
            $user->gender = $request->gender;
            $user->update();

            return response()->json([
                'error' => 0,
                'message' => 'Profile updated successfully',
                'data' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ]);
        }
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

            User::find($request->user()->id)->addMediaFromRequest('image')
                ->toMediaCollection('avatar')
                ->update(['is_verified' => 1]);

            DB::commit();
            return response()->json([
                'message' => 'Profile image updated successfully',
                'error' => 0,
                'profile_img' => $request->user()->avatar
            ]);

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

            User::find($request->user()->id)->addMediaFromRequest('image')
                ->toMediaCollection('cover_image')
                ->update(['is_verified' => 1]);

            DB::commit();
            return response()->json([
                'message' => 'Profile cover updated successfully',
                'error' => 0,
                'cover_img' => $request->user()->cover_image
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 1]);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => ['required'],
                'password' => [
                    'required', Password::min(8)->mixedCase()->numbers()->symbols()->letters(),
                    'confirmed'
                ],
            ]);
            $user = User::query()->find($request->user()->id);
            if(!Hash::check($request->current_password, $user->password)) {
                throw new \Exception('Current password does not match');
            }
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json(['message' => 'Password updated successfully', 'error' => 0], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 1]);
        }
    }

    public function getPosts(Request $request)
    {
        try {
            $query = Post::query()
                ->orderBy('created_at', 'desc')
                ->where('user_id', $request->user()->id);

            if($request->user()->hasRole('public_user')) {
                $query->where('type', 'public');
            }

            $posts = $query->paginate(30);

            return response()->json([
                'error' => 0,
                'data' => PostResource::collection($posts->load('repost'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function likedPosts(Request $request)
    {
        try {
            $posts = Post::query()
                ->orderBy('created_at', 'desc')
                ->whereHas('likes', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                })
                ->published()
                ->paginate(30);

            return response()->json([
                'error' => 0,
                'data' => PostResource::collection($posts)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function requestDeletion(Request $request)
    {
        try {
            $request->validate([
                'password' => 'required',
                'reason' => 'nullable|string|max:500'
            ]);

            $user = $request->user();
            
            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'error' => 1,
                    'message' => 'Password tidak sesuai'
                ], 400);
            }

            // Check if already requested
            if ($user->isDeletionRequested()) {
                return response()->json([
                    'error' => 1,
                    'message' => 'Account deletion sudah pernah diminta sebelumnya'
                ], 400);
            }

            $success = $this->deletionService->requestDeletion($user, $request->reason);

            if ($success) {
                return response()->json([
                    'error' => 0,
                    'message' => 'Account deletion requested. You have 30 days to reactivate.',
                    'scheduled_deletion_at' => $user->fresh()->scheduled_deletion_at?->toISOString()
                ]);
            }

            return response()->json([
                'error' => 1,
                'message' => 'Failed to request account deletion'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelDeletion(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->isDeletionRequested()) {
                return response()->json([
                    'error' => 1,
                    'message' => 'No active deletion request found'
                ], 400);
            }

            $success = $this->deletionService->reactivateAccount($user);

            if ($success) {
                return response()->json([
                    'error' => 0,
                    'message' => 'Account reactivated successfully. All your data has been restored.'
                ]);
            }

            return response()->json([
                'error' => 1,
                'message' => 'Failed to reactivate account'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getDeletionStatus(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'error' => 0,
                'data' => [
                    'account_status' => $user->account_status,
                    'deletion_requested_at' => $user->deletion_requested_at?->toISOString(),
                    'scheduled_deletion_at' => $user->scheduled_deletion_at?->toISOString(),
                    'days_remaining' => $user->scheduled_deletion_at ? now()->diffInDays($user->scheduled_deletion_at, false) : null,
                    'can_reactivate' => $user->canReactivate(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProfileController extends Controller
{
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
            return response()->json([
                'error' => 0,
                'data' => new UserResource($user)
            ]);
        } Catch (ValidationException $e) {
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

            $user = User::query()->find('id', $request->user()->id);
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

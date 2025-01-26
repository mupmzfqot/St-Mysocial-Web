<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Post;
use App\Models\User;
use App\Notifications\ChangeProfileImageStatus;
use App\Notifications\NewProfileImage;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProfileController extends Controller
{
    public function index($id = null)
    {
        $user = User::query()->find($id);
        $totalPost = Post::query()->where('user_id', $id)->count();
        $totalActivePost = Post::query()->published()->where('user_id', $id)->count();
        return Inertia::render('Profile/Index', compact('user', 'totalPost', 'totalActivePost'));
    }

    public function show($id = null)
    {
        $user = User::query()->find($id);
        $totalPosts = $user->posts()->count();
        $totalLikes = $user->likes()->count();
        $totalComments = $user->comments()->count();
        $requestUrl = route('user-post.tag-post', ['user_id' => $id]);

        return Inertia::render('Homepage/UserProfile',
            compact('user', 'totalPosts', 'totalLikes', 'totalComments', 'requestUrl')
        );
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        if(auth()->user()->hasRole('admin')) {
            return Inertia::render('Profile/Edit', [
                'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
                'status' => session('status'),
                'media' => [
                    'avatar' => $request->user()->getMedia('avatar')->first(),
                    'cover-image' => $request->user()->getMedia('cover-image')->first(),
                ]
            ]);
        }

        $totalPosts = auth()->user()?->posts()->count();
        $totalLikes = auth()->user()?->likes()->count();
        $totalComments = auth()->user()?->comments()->count();

        return  Inertia::render('Profile/UserEdit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'totalPosts' => $totalPosts,
            'totalLikes' => $totalLikes,
            'totalComments' => $totalComments,
            'media' => [
                'avatar' => $request->user()->getMedia('avatar')->first(),
                'cover-image' => $request->user()->getMedia('cover-image')->first(),
            ]
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'type' => 'required'
        ]);

        try {
            $user = auth()->user();
            DB::beginTransaction();
            $user->clearMediaCollection($request->type);
            $user->addMedia($request->file)->toMediaCollection($request->type)->update(['is_verified' => true]);

            DB::commit();

            $admins = getUserAdmin();
            Notification::send($admins, new NewProfileImage($user, 'avatar'));

        } catch (\Exception $e) {
            DB::rollBack();
        }

        return redirect()->back();
    }

    public function indexPhotos()
    {
        $photos = User::whereHas('media', function ($query) {
                $query->where('collection_name', 'avatar')
                    ->where('is_verified', true);
            })
            ->with(['media' => function ($query) {
                $query->where('collection_name', 'avatar')
                    ->where('is_verified', true);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Profile/Photos', compact('photos'));
    }

    public function indexCovers()
    {
        $photos = User::whereHas('media', function ($query) {
                $query->where('collection_name', 'cover_image')
                    ->where('is_verified', true);
            })
            ->with(['media' => function ($query) {
                $query->where('collection_name', 'cover_image')
                    ->where('is_verified', true);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Profile/Cover', compact('photos'));
    }

    public function updateProfileImageStatus(Request $request, $id)
    {
        $media = Media::where('id', $id)
            ->where('collection_name', $request->type)
            ->update(['is_verified' => $request->status]);



        $user = User::query()->whereHas('media', function ($query) use($id) { $query->where('id', $id); })->first();
        $status = $request->status == 1 ? 'approve' : 'remove';
        Notification::send($user, new ChangeProfileImageStatus($user, $request->type, $status));
        return redirect()->back();
    }
}

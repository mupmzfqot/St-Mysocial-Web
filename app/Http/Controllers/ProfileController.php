<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProfileController extends Controller
{
    public function index($id = null)
    {
        $user = User::query()->find($id);
        return Inertia::render('Profile/Index', compact('user'));
    }

    public function show($id = null)
    {
        $user = User::query()->find($id);
        return Inertia::render('Homepage/UserProfile', compact('user'));
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

        return  Inertia::render('Profile/UserEdit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
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
            $user->addMedia($request->file)->toMediaCollection($request->type);
            DB::commit();


        } catch (\Exception $e) {
            DB::rollBack();
        }

        return redirect()->back();
    }

    public function indexPhotos()
    {
        $photos = User::whereHas('media', function ($query) {
            $query->where('collection_name', 'avatar')
                ->where('is_verified', false);
        })->paginate(10);

        return Inertia::render('Profile/Photos', compact('photos'));
    }

    public function updateProfileImageStatus(Request $request, $id)
    {
        Media::where('id', $id)->update(['is_verified' => $request->status]);
        return redirect()->back();
    }
}

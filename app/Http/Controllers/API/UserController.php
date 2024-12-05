<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
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
            ->whereNot('id', $request->user()->id)
            ->orderBy('name', 'asc')
            ->paginate(20);

        return UserResource::collection($users);
    }

    public function getMedia(Request $request)
    {
        $user = $request->user()->id;

        $media = Media::query()->get();

        return $media;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function get()
    {
        $cacheKey = 'team_users_' . (auth()->user() ? auth()->user()->id : 'guest');
        $cacheDuration = now()->addMinutes(5);

        return \Cache::remember($cacheKey, $cacheDuration, function () {
                $query = User::query()->isActive()
                    ->whereHas('roles', function ($query) {
                        $query->whereIn('name', ['user']);
                    })
                    ->withCount(['posts as posts_count' => function ($query) {
                        $query->where('published', 1);
                    }])
                    ->whereNotNull('email_verified_at')
                    ->where('id', '!=', auth()->user()->id)
                    ->orderBy('posts_count', 'desc');

                return $query->get();
        });
    }
}

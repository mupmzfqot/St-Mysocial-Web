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
        $query = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->whereNotNull('email_verified_at')
            ->where('is_active', true)
            ->where('id', '!=', auth()->user()->id);

        return $query->get();
    });
}
}

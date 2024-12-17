<?php

use App\Models\User;
use Illuminate\Support\Collection;

if (!function_exists('getUserAdmin')) {
    function getUserAdmin(): array|Collection
    {
        return User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();
    }
}

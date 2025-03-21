<?php

namespace App\Actions;

use App\Models\User;

class UserCount
{
    public function handle(): array
    {
        $userCount = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'user');
        })->count();

        $publicUserCount = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'public_user');
        })->count();

        $adminCount = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->count();

        return [
            'user_count' => $userCount,
            'public_user_count' => $publicUserCount,
            'admin_count' => $adminCount,
        ];
    }
}

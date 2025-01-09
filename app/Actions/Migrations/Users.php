<?php

namespace App\Actions\Migrations;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Users
{
    public function handle()
    {
        DB::connection('mysql_2')
            ->table('users')
            ->select('id', 'email', 'fullname', 'surname', 'account_type', 'regtime', 'originPhotoUrl', 'originCoverUrl', 'removed')
            ->chunkById(1000, function ($users) {
                $usersData = [];
                $roleData = [];

                foreach ($users as $user) {
                    $usersData[] = [
                        'id'                => $user->id,
                        'email'             => $user->email,
                        'username'          => $user->surname,
                        'name'              => $user->fullname,
                        'email_verified_at' => now(),
                        'is_active'         => !in_array($user->account_type, [9, 11]) && $user->removed == 0,
                        'password'          => Hash::make('userPass2025@ST'),
                        'created_at'        => date('Y-m-d H:i:s', $user->regtime),
                        'updated_at'        => date('Y-m-d H:i:s', $user->regtime),
                    ];

                    // Collect roles for batch assignment
                    $roleData[$user->id] = $this->getRoleByAccountType($user->account_type);
                }

                // Bulk insert/update users
                User::upsert($usersData, ['email'], [
                    'id', 'username', 'name', 'email_verified_at', 'is_active', 'password', 'created_at', 'updated_at'
                ]);

                // Assign roles in bulk after users are inserted or updated
                foreach ($roleData as $userId => $role) {
                    $user = User::find($userId);
                    if ($user) {
                        $user->assignRole($role);
                    }
                }

                // Upload images asynchronously
                foreach ($users as $user) {
                    if ($user->originPhotoUrl) {
                        $this->uploadImage($user->originPhotoUrl, $user, 'avatar');
                    }

                    if ($user->originCoverUrl) {
                        $this->uploadImage($user->originCoverUrl, $user, 'cover_image');
                    }
                }
            });

        Artisan::call('db:seed', ['--class' => 'UserSeeder']);
    }

    private function getRoleByAccountType($accountType): string
    {
        $roleMap = [
            10 => 'admin',
            0  => 'user',
        ];

        return $roleMap[$accountType] ?? 'public_user';
    }

    private function uploadImage($url, $newUser, $type): void
    {
        if($url) {
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? null;
            if($path && file_exists(public_path($path))) {
                $user = User::find($newUser->id);
                if ($user) {
                    $user->addMedia(public_path($parsedUrl['path']))
                        ->toMediaCollection($type)->update(['is_verified' => true]);
                }
            }
        }
    }
}

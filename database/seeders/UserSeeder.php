<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(['username' => 'administrator'], [
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
            'password' => bcrypt('pass4321@st!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->syncRoles('admin');
    }
}

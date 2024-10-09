<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'display_name' => 'Admin',
                'name' => 'admin'
            ],
            [
                'display_name' => 'ST User',
                'name' => 'user'
            ],
            [
                'display_name' => 'Public User',
                'name' => 'public_user'
            ]
        ];

        foreach ($roles as $role) {
            Role::query()->firstOrCreate(['name' => $role['name']], ['display_name' => $role['display_name']]);
        }
    }
}

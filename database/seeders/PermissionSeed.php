<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          Permission::create([
            'name' => 'create_user',

            'guard_name' => 'web',
        ]);

        Permission::create([
            'name' => 'update_user',

            'guard_name' => 'web',
        ]);

        Permission::create([
            'name' => 'delete_user',

            'guard_name' => 'web',
        ]);

        Permission::create([
            'name' => 'view_user',

            'guard_name' => 'web',
        ]);

        Permission::create([
            'name' => 'create_role',

            'guard_name' => 'web',
        ]);

        Permission::create([
            'name' => 'update_role',

            'guard_name' => 'web',
        ]);

        Permission::create([
            'name' => 'delete_role',

            'guard_name' => 'web',
        ]);

        Permission::create([
            'name' => 'view_role',

            'guard_name' => 'web',
        ]);

        Permission::create([
            'name' => 'dashboard',

            'guard_name' => 'web',
        ]);

        Permission::create([
            'name' => 'profile',

            'guard_name' => 'web',
        ]);
    }
}

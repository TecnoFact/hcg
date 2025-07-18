<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      $admin = Role::create([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

      $customer = Role::create([
            'name' => 'Customer',
            'guard_name' => 'web',
        ]);

        $admin->givePermissionTo([
                'create_user',
                'update_user',
                'delete_user',
                'view_user',
                'create_role',
                'update_role',
                'delete_role',
                'view_role',
                'dashboard',
                'profile',
            ]);

        $customer->givePermissionTo([
            'view_user',
            'profile',
            'dashboard',
        ]);
    }
}

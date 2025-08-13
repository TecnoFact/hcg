<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
       //User::truncate();

        $user = User::firstOrCreate([
            'email' => 'admin@cfdi.test',
        ], [
            'name' => 'Admin CFDI',
            'password' => Hash::make('12345678'),
        ]);
        $user->assignRole('Admin');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
       //User::truncate();

       $user = User::create([
            'email' => 'admin@cfdi.test',
            'name' => 'Admin CFDI',
            'password' => Hash::make('12345678'),
        ]);
        $user->assignRole('Admin');
    }
}

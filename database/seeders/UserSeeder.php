<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate([
            'email' => 'customer@cfdi.test',
        ], [
            'name' => 'customer CFDI',
            'password' => Hash::make('12345678') // nunca guardes contraseñas sin cifrar
        ]);

        $user->assignRole('Customer');
    }
}


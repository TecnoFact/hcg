<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin CFDI',
            'email' => 'admin@cfdi.test',
            'password' => Hash::make('12345678') // nunca guardes contraseñas sin cifrar
        ]);
    }
}


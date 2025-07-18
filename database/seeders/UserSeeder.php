<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {


        $user = User::create([
            'name' => 'customer CFDI',
            'email' => 'customer@cfdi.test',
            'password' => Hash::make('12345678') // nunca guardes contraseñas sin cifrar
        ]);

        $user->assignRole('Customer');
    }
}


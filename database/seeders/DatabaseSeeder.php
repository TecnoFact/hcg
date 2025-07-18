<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CatalogoFormaPagoSeeder::class,
            CatalogoMetodoPagoSeeder::class,
            CatalogoClaveUnidadSeed::class,
            CitySeed::class,
            CountrySeed::class,
            RegimenFiscalSeed::class,
            StateSeed::class,
            PermissionSeed::class,
            RoleSeed::class,
            AdminUserSeeder::class,
            UserSeeder::class,
        ]);
    }
}

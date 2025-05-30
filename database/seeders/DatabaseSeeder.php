<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Llama a los seeders en el orden correcto para respetar las claves foráneas
        $this->call([
            EmpresaSeeder::class,
            TipoCombustibleSeeder::class,
            RoleSeeder::class,
            UserSeeder::class, // User depende de Ueb
            ChoferSeeder::class,
            VehiculoSeeder::class, // Vehiculo depende de Ueb, TipoCombustible, User
            TarjetaCombustibleSeeder::class, // TarjetaCombustible depende de TipoCombustible, Vehiculo, Ueb, User
            CargaCombustibleSeeder::class, // CargaCombustible depende de TipoCombustible, User, TarjetaCombustible
            // Agrega aquí otros seeders si los creas
        ]);
    }
}


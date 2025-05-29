<?php

namespace Database\Seeders;

use App\Models\Vehiculo;
use App\Models\Ueb; // Importar Ueb
use App\Models\TipoCombustible; // Importar TipoCombustible
use App\Models\User; // Importar User
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehiculoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarse de que haya UEBs, Tipos de Combustible y Usuarios creados
        $uebs = Ueb::all();
        $tiposCombustible = TipoCombustible::all();
        $users = User::all();

         if ($uebs->isEmpty()) {
            $this->call(UebSeeder::class);
            $uebs = Ueb::all();
        }

        if ($tiposCombustible->isEmpty()) {
            $this->call(TipoCombustibleSeeder::class);
            $tiposCombustible = TipoCombustible::all();
        }

         if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }


        // Crea 20 vehículos, asignándolos a UEBs, Tipos de Combustible y Usuarios aleatorios (o nulo)
        Vehiculo::factory()->count(20)->create([
            'ueb_id' => $uebs->random()->id,
            'tipo_combustible_id' => $tiposCombustible->random()->id,
            // Asigna un usuario aleatorio o null
            'user_id' => $users->random()->id ?? null, // Puede ser nulo
        ]);
    }
}

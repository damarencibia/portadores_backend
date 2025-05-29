<?php

namespace Database\Seeders;

use App\Models\TarjetaCombustible;
use App\Models\TipoCombustible; // Importar TipoCombustible
use App\Models\Vehiculo; // Importar Vehiculo
use App\Models\Ueb; // Importar Ueb
use App\Models\User; // Importar User
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TarjetaCombustibleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarse de que haya Tipos de Combustible, Vehículos, UEBs y Usuarios creados
        $tiposCombustible = TipoCombustible::all();
        $vehiculos = Vehiculo::all();
        $uebs = Ueb::all();
        $users = User::all();

         if ($tiposCombustible->isEmpty()) {
            $this->call(TipoCombustibleSeeder::class);
            $tiposCombustible = TipoCombustible::all();
        }

         if ($vehiculos->isEmpty()) {
            $this->call(VehiculoSeeder::class);
            $vehiculos = Vehiculo::all();
        }

         if ($uebs->isEmpty()) {
            $this->call(UebSeeder::class);
            $uebs = Ueb::all();
        }

         if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }


        // Crea 30 tarjetas de combustible, asignándolas aleatoriamente
        TarjetaCombustible::factory()->count(30)->create([
            'tipo_combustible_id' => $tiposCombustible->random()->id,
            // Asigna un vehículo aleatorio o null
            'vehiculo_id' => $vehiculos->random()->id ?? null, // Puede ser nulo
            'ueb_id' => $uebs->random()->id,
            // Asigna un usuario aleatorio
            'user_id' => $users->random()->id,
        ]);
    }
}


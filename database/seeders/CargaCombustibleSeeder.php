<?php

namespace Database\Seeders;

use App\Models\CargaCombustible;
use App\Models\TipoCombustible; // Importar TipoCombustible
use App\Models\User; // Importar User
use App\Models\TarjetaCombustible; // Importar TarjetaCombustible
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CargaCombustibleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarse de que haya Tipos de Combustible, Usuarios y Tarjetas de Combustible creados
        $tiposCombustible = TipoCombustible::all();
        $users = User::all();
        $tarjetas = TarjetaCombustible::all();

         if ($tiposCombustible->isEmpty()) {
            $this->call(TipoCombustibleSeeder::class);
            $tiposCombustible = TipoCombustible::all();
        }

         if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }

         if ($tarjetas->isEmpty()) {
            $this->call(TarjetaCombustibleSeeder::class);
            $tarjetas = TarjetaCombustible::all();
        }


        // Crea 100 cargas de combustible
        CargaCombustible::factory()->count(100)->create([
            'tipo_combustible_id' => $tiposCombustible->random()->id,
            'registrado_por_id' => $users->random()->id,
            // Asigna un validador aleatorio o null
            'validado_por_id' => $users->random()->id ?? null, // Puede ser nulo
            // Asigna una tarjeta aleatoria
            'tarjeta_combustible_id' => $tarjetas->random()->id,
        ]);
    }
}


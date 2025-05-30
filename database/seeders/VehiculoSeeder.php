<?php

namespace Database\Seeders;

use App\Models\Vehiculo;
use App\Models\Empresa; // Importar Empresa
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
        // Asegurarse de que haya empresas, Tipos de Combustible y Usuarios creados
        $empresas = Empresa::all();
        $tiposCombustible = TipoCombustible::all();
        $users = User::all();

         if ($empresas->isEmpty()) {
            $this->call(empresaseeder::class);
            $empresas = Empresa::all();
        }

        if ($tiposCombustible->isEmpty()) {
            $this->call(TipoCombustibleSeeder::class);
            $tiposCombustible = TipoCombustible::all();
        }

         if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }


        // Crea 20 vehículos, asignándolos a empresas, Tipos de Combustible y Usuarios aleatorios (o nulo)
        Vehiculo::factory()->count(20)->create([
            'empresa_id' => $empresas->random()->id,
            'tipo_combustible_id' => $tiposCombustible->random()->id,
            // Asigna un usuario aleatorio o null
            'user_id' => $users->random()->id ?? null, // Puede ser nulo
        ]);
    }
}

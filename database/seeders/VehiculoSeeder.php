<?php

namespace Database\Seeders;

use App\Models\Vehiculo;
use App\Models\Empresa;
use App\Models\TipoCombustible;
use App\Models\Chofer; // ¡Importar Chofer!
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehiculoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarse de que haya empresas, Tipos de Combustible y Choferes creados
        $empresas = Empresa::all();
        $tiposCombustible = TipoCombustible::all();
        $choferes = Chofer::all(); // Obtener todos los choferes

        if ($empresas->isEmpty()) {
            $this->call(EmpresaSeeder::class);
            $empresas = Empresa::all();
        }

        if ($tiposCombustible->isEmpty()) {
            $this->call(TipoCombustibleSeeder::class);
            $tiposCombustible = TipoCombustible::all();
        }

        if ($choferes->isEmpty()) {
            $this->call(ChoferSeeder::class); // Asegúrate de que este seeder crea choferes SIN vehiculo_id
            $choferes = Chofer::all();
        }

        // Crea 20 vehículos, asignándolos a empresas, Tipos de Combustible y Choferes aleatorios
        Vehiculo::factory()->count(20)->create([
            'empresa_id' => $empresas->random()->id,
            'tipo_combustible_id' => $tiposCombustible->random()->id,
            'chofer_id' => $choferes->random()->id, // Asignar un chofer existente
        ]);
    }
}

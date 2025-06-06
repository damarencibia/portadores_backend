<?php

namespace Database\Seeders;

use App\Models\Chofer;
use App\Models\Empresa;
// use App\Models\Vehiculo; // No necesitamos importar Vehiculo si la FK está en Vehiculo
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChoferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresas = Empresa::all();

        if ($empresas->isEmpty()) {
            $this->call(EmpresaSeeder::class);
            $empresas = Empresa::all();
        }

        Chofer::factory()->count(10)->create([
            'empresa_id' => $empresas->random()->id,
            // 'vehiculo_id' => null, // No asignar vehiculo_id aquí, se asigna en VehiculoFactory
        ]);
    }
}

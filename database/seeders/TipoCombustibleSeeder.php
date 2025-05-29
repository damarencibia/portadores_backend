<?php

namespace Database\Seeders;

use App\Models\TipoCombustible;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoCombustibleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crea algunos tipos de combustible específicos o usa el factory
         TipoCombustible::create(['nombre' => 'Diesel', 'unidad_medida' => 'Litros', 'precio' => 25.00]);
         TipoCombustible::create(['nombre' => 'Gasolina Regular', 'unidad_medida' => 'Litros', 'precio' => 30.00]);
         TipoCombustible::create(['nombre' => 'Gasolina Especial', 'unidad_medida' => 'Litros', 'precio' => 35.00]);
         TipoCombustible::create(['nombre' => 'Gas Natural', 'unidad_medida' => 'm3', 'precio' => 15.00]);

        // O crea tipos aleatorios usando el factory
        // TipoCombustible::factory()->count(5)->create();
    }
}


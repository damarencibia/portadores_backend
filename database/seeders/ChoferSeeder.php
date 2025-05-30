<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Chofer; // Import the Chofer model
use App\Models\Empresa; // Import the Empresa model

class ChoferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Obtener todas las empresas existentes
        $empresas = Empresa::all();

        // Verificar si existen empresas para asignar
        if ($empresas->isEmpty()) {
            // Si no hay empresas, puedes optar por crear algunas aquí
            // o simplemente mostrar un mensaje y no crear choferes.
            // Por ejemplo, para crear 3 empresas si no existen:
            // Empresa::factory()->count(3)->create();
            // $empresas = Empresa::all(); // Volver a cargar las empresas

            // Si decides no crear empresas y no hay ninguna,
            // es mejor detener la ejecución o manejarlo según tu lógica.
            $this->command->info('No hay empresas en la base de datos. No se crearán choferes o se crearán asociados a nuevas empresas según la factory.');
            // Si la ChoferFactory está configurada para crear una Empresa si empresa_id no se provee,
            // aún podrías usar: Chofer::factory()->count(10)->create();
            // Pero si quieres forzar la asignación a empresas *existentes*, y no hay,
            // entonces no se deberían crear choferes o se debería manejar el error.
        }

        if ($empresas->isNotEmpty()) {
            // Crear 10 registros de Chofer usando la factory
            // y asignar una empresa aleatoria a cada uno
            Chofer::factory()->count(10)->make()->each(function ($chofer) use ($empresas) {
                // Asignar una empresa aleatoria de las existentes
                $chofer->empresa_id = $empresas->random()->id;
                $chofer->save();
            });
            $this->command->info('Se han creado 10 choferes y asignado a empresas existentes aleatoriamente.');
        } else {
            // Si después de intentar crear/cargar empresas, aún no hay,
            // puedes decidir simplemente usar la factory tal cual (creará empresas nuevas por chofer)
            $this->command->warn('No se encontraron empresas. Los choferes se crearán con la lógica por defecto de la factory para empresa_id.');
            Chofer::factory()->count(10)->create();
        }
    }
}

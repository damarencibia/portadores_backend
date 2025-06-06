<?php

namespace Database\Seeders;

use App\Models\TarjetaCombustible;
use App\Models\TipoCombustible;
use App\Models\Vehiculo;
use App\Models\Chofer; // Importar Chofer
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TarjetaCombustibleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarse de que haya Tipos de Combustible, Vehículos y Choferes creados
        $tiposCombustible = TipoCombustible::all();
        $vehiculos = Vehiculo::all(); // Obtener todos los vehículos
        $choferes = Chofer::all();   // Obtener todos los choferes

        // Llamar a los seeders necesarios si las tablas están vacías
        if ($tiposCombustible->isEmpty()) {
            $this->call(TipoCombustibleSeeder::class);
            $tiposCombustible = TipoCombustible::all();
        }

        // Si los vehículos están vacíos, crearlos.
        // Importante: VehiculoSeeder debe crear vehículos y asignarles un chofer_id.
        if ($vehiculos->isEmpty()) {
            $this->call(VehiculoSeeder::class);
            $vehiculos = Vehiculo::all();
            $choferes = Chofer::all(); // Recargar choferes porque VehiculoSeeder pudo haber creado nuevos
        }

        // Filtramos los choferes que tienen un vehículo asociado.
        // Esto es crucial para asegurar que la tarjeta se asigne a un chofer que, a su vez, tiene un vehículo.
        $choferesConVehiculo = $choferes->filter(function ($chofer) {
            return $chofer->vehiculo !== null;
        });

        // Si no hay choferes con vehículo, o no hay tipos de combustible, no podemos crear tarjetas.
        if ($choferesConVehiculo->isEmpty() || $tiposCombustible->isEmpty()) {
            $this->command->info('No hay choferes con vehículos asociados o tipos de combustible para crear Tarjetas de Combustible.');
            return;
        }

        // Crea 30 tarjetas de combustible, asignándolas aleatoriamente
        // Ahora, nos aseguramos de que el chofer seleccionado tenga un vehículo.
        TarjetaCombustible::factory()->count(30)->create([
            'tipo_combustible_id' => $tiposCombustible->random()->id,
            'chofer_id' => $choferesConVehiculo->random()->id, // Asigna un chofer que sabemos tiene un vehículo
        ]);
    }
}

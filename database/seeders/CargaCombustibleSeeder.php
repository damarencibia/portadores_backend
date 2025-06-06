<?php

namespace Database\Seeders;

use App\Models\CargaCombustible;
use App\Models\TipoCombustible;
use App\Models\User;
use App\Models\TarjetaCombustible;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CargaCombustibleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarse de que haya Usuarios y Tarjetas de Combustible creados.
        // El TipoCombustible se infiere de la TarjetaCombustible.
        $users = User::all();
        $tarjetas = TarjetaCombustible::all();

        // Llama a los seeders necesarios si las tablas están vacías
        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        if ($tarjetas->isEmpty()) {
            // Asegúrate de que TarjetaCombustibleSeeder crea tarjetas con chofer y vehículo
            $this->call(TarjetaCombustibleSeeder::class);
            $tarjetas = TarjetaCombustible::all();
        }

        // Si no hay usuarios o tarjetas, no podemos crear cargas.
        if ($users->isEmpty() || $tarjetas->isEmpty()) {
            $this->command->info('No hay usuarios o tarjetas de combustible para crear Cargas de Combustible.');
            return;
        }

        // Crea 100 cargas de combustible
        CargaCombustible::factory()->count(100)->create([
            // 'tipo_combustible_id' ya no se asigna directamente aquí, se infiere de la tarjeta
            'registrado_por_id' => $users->random()->id,
            // Asigna un validador aleatorio o null
            'validado_por_id' => $users->random()->id ?? null, // Puede ser nulo
            // Asigna una tarjeta aleatoria
            'tarjeta_combustible_id' => $tarjetas->random()->id,
        ]);
    }
}

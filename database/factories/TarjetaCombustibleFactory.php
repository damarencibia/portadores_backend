<?php

namespace Database\Factories;

use App\Models\TarjetaCombustible;
use App\Models\TipoCombustible; // Importar TipoCombustible
use App\Models\Vehiculo; // Importar Vehiculo
use App\Models\Ueb; // Importar Ueb
use App\Models\User; // Importar User
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TarjetaCombustible>
 */
class TarjetaCombustibleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Asegurarse de que haya al menos un TipoCombustible, Vehiculo, Ueb y User creados
        $tipoCombustible = TipoCombustible::inRandomOrder()->first() ?? TipoCombustible::factory()->create();
        $vehiculo = Vehiculo::inRandomOrder()->first(); // Puede ser nulo si no hay vehículos aún
        $ueb = Ueb::inRandomOrder()->first() ?? Ueb::factory()->create();
        $user = User::inRandomOrder()->first() ?? User::factory()->create();


        return [
            'numero' => $this->faker->unique()->randomNumber(8), // Número de tarjeta único
            'tipo_combustible_id' => $tipoCombustible->id, // Usar un TipoCombustible existente
            'fecha_vencimiento' => $this->faker->dateTimeBetween('now', '+5 years')->format('Y-m-d'), // Fecha de vencimiento en el futuro
            // Asigna un vehículo aleatorio existente o null
            'vehiculo_id' => $this->faker->boolean(80) ? ($vehiculo ? $vehiculo->id : null) : null, // 80% de probabilidad de asignar un vehículo existente
            'ueb_id' => $ueb->id, // Usar una UEB existente
            'activa' => $this->faker->boolean(), // Indica si está activa
            'user_id' => $user->id, // Usar un usuario existente
        ];
    }
}


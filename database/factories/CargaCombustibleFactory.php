<?php

namespace Database\Factories;

use App\Models\CargaCombustible;
// use App\Models\TipoCombustible; // Eliminado: Se infiere de la tarjeta
use App\Models\User;
use App\Models\TarjetaCombustible;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CargaCombustible>
 */
class CargaCombustibleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Asegurarse de que haya al menos un Usuario y TarjetaCombustible creados
        $registrador = User::inRandomOrder()->first() ?? User::factory()->create();
        $validador = User::inRandomOrder()->first(); // Puede ser nulo si no hay suficientes usuarios

        // Asegurarse de que la tarjeta de combustible exista.
        // Opcional: Podrías querer que esta tarjeta tenga un chofer y un vehículo para consistencia.
        // Para simplificar el factory, asumimos que TarjetaCombustibleFactory ya maneja esto.
        $tarjeta = TarjetaCombustible::inRandomOrder()->first() ?? TarjetaCombustible::factory()->create();

        // El tipo de combustible se infiere de la tarjeta, no se asigna directamente aquí.
        // $tipoCombustible = TipoCombustible::inRandomOrder()->first() ?? TipoCombustible::factory()->create();

        return [
            'fecha' => $this->faker->date(),
            'hora' => $this->faker->time(),
            // 'tipo_combustible_id' => $tipoCombustible->id, // ¡ELIMINADO! Se infiere de la tarjeta
            'cantidad' => $this->faker->randomFloat(2, 10, 100),
            'odometro' => $this->faker->numberBetween(1000, 500000),
            'lugar' => $this->faker->city(),
            'motivo' => $this->faker->sentence(),
            'no_chip' => $this->faker->boolean(50) ? $this->faker->unique()->numerify('CHIP-########') : null, // Añadido no_chip si aplica
            'registrado_por_id' => $registrador->id,
            'validado_por_id' => $this->faker->boolean(80) ? ($validador ? $validador->id : null) : null,
            'fecha_validacion' => $this->faker->boolean(80) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'estado' => $this->faker->randomElement(['Pendiente', 'Validada', 'Rechazada']),
            'importe' => $this->faker->randomFloat(2, 100, 1000),
            'tarjeta_combustible_id' => $tarjeta->id,
        ];
    }
}

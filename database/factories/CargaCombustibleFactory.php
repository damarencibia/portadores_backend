<?php

namespace Database\Factories;

use App\Models\CargaCombustible;
use App\Models\TipoCombustible; // Importar TipoCombustible
use App\Models\User; // Importar User
use App\Models\TarjetaCombustible; // Importar TarjetaCombustible
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
        // Asegurarse de que haya al menos un TipoCombustible, Usuario y TarjetaCombustible creados
        $tipoCombustible = TipoCombustible::inRandomOrder()->first() ?? TipoCombustible::factory()->create();
        $registrador = User::inRandomOrder()->first() ?? User::factory()->create();
        $validador = User::inRandomOrder()->first(); // Puede ser nulo si no hay suficientes usuarios
        $tarjeta = TarjetaCombustible::inRandomOrder()->first() ?? TarjetaCombustible::factory()->create();


        return [
            'fecha' => $this->faker->date(), // Fecha de la carga
            'hora' => $this->faker->time(), // Hora de la carga
            'tipo_combustible_id' => $tipoCombustible->id, // Usar un TipoCombustible existente
            'cantidad' => $this->faker->randomFloat(2, 10, 100), // Cantidad cargada
            'odometro' => $this->faker->numberBetween(1000, 500000), // Lectura del odómetro
            'lugar' => $this->faker->city(), // Lugar de la carga
            'registrado_por_id' => $registrador->id, // Usuario que registra
            // Asigna un validador aleatorio existente o null
            'validado_por_id' => $this->faker->boolean(80) ? ($validador ? $validador->id : null) : null, // 80% de probabilidad de estar validado
            'fecha_validacion' => $this->faker->boolean(80) ? $this->faker->dateTimeBetween('-1 month', 'now') : null, // Fecha de validación (si está validado)
            'estado' => $this->faker->randomElement(['Pendiente', 'Validada', 'Rechazada']), // Estado de la carga
            'importe' => $this->faker->randomFloat(2, 100, 1000), // Importe de la carga
            'tarjeta_combustible_id' => $tarjeta->id, // Usar una TarjetaCombustible existente
        ];
    }
}


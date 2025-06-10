<?php

namespace Database\Factories;

use App\Models\Vehiculo;
use App\Models\TipoCombustible;
use App\Models\Empresa;
use App\Models\Chofer; // ¡Importar el modelo Chofer!
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehiculo>
 */
class VehiculoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Asegurarse de que haya al menos un TipoCombustible, una Empresa y un Chofer creados
        $tipoCombustible = TipoCombustible::inRandomOrder()->first() ?? TipoCombustible::factory()->create();
        $empresa = Empresa::inRandomOrder()->first() ?? Empresa::factory()->create();
        // Creamos un chofer para asignárselo al vehículo.
        // Aseguramos que el chofer creado no tenga ya un vehículo asignado (si es que estás controlando eso en ChoferFactory).
        // Para la relación 1:1, es importante que el chofer_id en el vehículo sea único.
        $chofer = Chofer::factory()->create(); // Crea un nuevo chofer para este vehículo

        return [
            'numero_interno' => $this->faker->unique()->randomNumber(5),
            'marca' => $this->faker->word(),
            'modelo' => $this->faker->word(),
            'tipo_vehiculo' => $this->faker->randomElement(['Auto', 'Camioneta', 'Moto', 'Camión']),
            'ano' => $this->faker->year(),
            'tipo_combustible_id' => $tipoCombustible->id,
            'indice_consumo' => $this->faker->randomFloat(2, 5, 20),
            'prueba_litro' => $this->faker->randomFloat(2, 5, 20),
            'ficav' => $this->faker->date(),
            'capacidad_tanque' => $this->faker->numberBetween(30, 100),
            'color' => $this->faker->colorName(),
            'chapa' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'numero_motor' => $this->faker->unique()->uuid(),
            'empresa_id' => $empresa->id,
            'numero_chasis' => $this->faker->unique()->uuid(),
            'estado_tecnico' => $this->faker->randomElement(['activo', 'paralizado', 'en_reparacion']),
            'chofer_id' => $chofer->id, // ¡NUEVO! Asignar el ID del chofer
        ];
    }

    /**
     * Configure the model factory.
     * Ensure that when a Vehiculo is created, its associated Chofer is also created
     * and linked, and vice-versa if needed.
     */
    public function configure()
    {
        return $this->afterCreating(function (Vehiculo $vehiculo) {
            // Si el chofer_id ya fue asignado en la definición, no hacemos nada.
            // Esto es para asegurar que el chofer creado en la definición se vincule con el vehículo.
            // Si el chofer_id es nullable y no se asigna en la definición, podrías crear un chofer aquí.
        });
    }
}

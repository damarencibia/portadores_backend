<?php

namespace Database\Factories;

use App\Models\Ueb;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ueb>
 */
class UebFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company, // Nombre de la UEB
            // 'code' es auto-generado en el modelo Ueb boot method
            'direccion' => $this->faker->address, // Dirección de la UEB
        ];
    }
}


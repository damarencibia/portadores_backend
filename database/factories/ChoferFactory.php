<?php

namespace Database\Factories;

use App\Models\Empresa; // Importar el modelo Empresa
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chofer>
 */
class ChoferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name(), // Nombre del usuario
            'apellidos' => $this->faker->lastName(), // Nombre del usuario
            'email' => $this->faker->unique()->safeEmail(), // Correo electrónico único y seguro
            'empresa_id' => Empresa::factory(), // Crea una nueva Empresa o usa una existente
            
        ];
    }
}

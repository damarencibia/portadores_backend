<?php

namespace Database\Factories;

use App\Models\TipoCombustible;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TipoCombustible>
 */
class TipoCombustibleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Lista de nombres de combustible.
        // Si necesitas crear más de 4 tipos únicos, expande esta lista.
        // Si solo necesitas los 4 definidos en el seeder, puedes quitar unique()
        // o asegurarte de no llamar a este factory más de 4 veces para crear nuevos.
        $combustibles = ['Diesel', 'Gasolina Regular', 'Gasolina Especial', 'Gas Natural', 'Biodiesel', 'GLP']; // Ampliado ligeramente
        $unidadMedida = ['Litros', 'Galones', 'm3']; // Ajustar según la unidad real

        return [
            // Usamos unique() aquí solo si esperamos crear múltiples tipos con nombres únicos
            // Si solo creas los 4 del seeder, puedes quitar unique().
            'nombre' => $this->faker->unique()->randomElement($combustibles),
            'unidad_medida' => $this->faker->randomElement($unidadMedida), // Unidad de medida
            'precio' => $this->faker->randomFloat(2, 10, 50), // Precio por unidad
        ];
    }
}


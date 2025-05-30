<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Empresa; // Importar el modelo Empresa
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(), // Nombre del usuario
            'email' => $this->faker->unique()->safeEmail(), // Correo electrónico único y seguro
            'email_verified_at' => now(), // Fecha de verificación de correo (ahora)
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10), // Token "recordarme"
            'empresa_id' => Empresa::factory(), // Crea una nueva Empresa o usa una existente
            // El rol se asignará en el Seeder
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}


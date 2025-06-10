<?php
namespace Database\Factories;

use App\Models\User;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName,
            'lastname' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'password' => bcrypt('password'), // Cambia esto si quieres una password real
            'roles' => $this->faker->randomElement(['operador', 'supervisor', 'admin']),
            'remember_token' => Str::random(10),
            'empresa_id' => Empresa::factory(),
        ];
    }
}

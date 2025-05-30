<?php

namespace Database\Factories;

use App\Models\Vehiculo;
use App\Models\TipoCombustible; // Importar el modelo TipoCombustible
use App\Models\Empresa; // Importar el modelo Empresa
use App\Models\User; // Importar el modelo User
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
         // Asegurarse de que haya al menos un TipoCombustible y una Empresa creados
        $tipoCombustible = TipoCombustible::inRandomOrder()->first() ?? TipoCombustible::factory()->create();
        $Empresa = Empresa::inRandomOrder()->first() ?? Empresa::factory()->create();
        $user = User::inRandomOrder()->first(); // Puede ser nulo si no hay usuarios aún

        return [
            'numero_interno' => $this->faker->unique()->randomNumber(5), // Número interno único
            'marca' => $this->faker->word(), // Marca del vehículo
            'modelo' => $this->faker->word(), // Modelo del vehículo
            'ano' => $this->faker->year(), // Año de fabricación
            'tipo_combustible_id' => $tipoCombustible->id, // Usar un TipoCombustible existente
            'indice_consumo' => $this->faker->randomFloat(2, 5, 20), // Índice de consumo (L/100km)
            'prueba_litro' => $this->faker->randomFloat(2, 5, 20), // Prueba por litro (km/L)
            'ficav' => $this->faker->boolean(), // FICAV (boolean según tu migración)
            'capacidad_tanque' => $this->faker->numberBetween(30, 100), // Capacidad del tanque
            'color' => $this->faker->colorName(), // Color del vehículo
            'chapa' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'), // Chapa única (ej: ABC123)
            'numero_motor' => $this->faker->unique()->uuid(), // Número de motor único
            'activo' => $this->faker->boolean(), // Indica si está activo
            'empresa_id' => $Empresa->id, // Usar una Empresa existente
            'numero_chasis' => $this->faker->unique()->uuid(), // Número de chasis único
            'estado_tecnico' => $this->faker->randomElement(['Operativo', 'En Reparación', 'Baja']), // Estado técnico
            // Asigna un usuario aleatorio existente o null
            'user_id' => $this->faker->boolean(70) ? ($user ? $user->id : null) : null, // 70% de probabilidad de asignar un usuario existente
        ];
    }
}


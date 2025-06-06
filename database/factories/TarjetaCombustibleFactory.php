<?php

namespace Database\Factories;

use App\Models\TarjetaCombustible;
use App\Models\TipoCombustible;
use App\Models\Vehiculo;
use App\Models\Chofer;
use App\Models\Empresa;
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
        // Asegurarse de que haya al menos un TipoCombustible y una Empresa creados
        $tipoCombustible = TipoCombustible::inRandomOrder()->first() ?? TipoCombustible::factory()->create();
        $empresa = Empresa::inRandomOrder()->first() ?? Empresa::factory()->create();

        // Buscar un chofer que tenga un vehículo asociado.
        // Si no existe, crear un chofer y un vehículo para él.
        $chofer = Chofer::has('vehiculo')->inRandomOrder()->first();
        if (!$chofer) {
            // Si no hay choferes con vehículos, creamos uno.
            // Asegúrate que ChoferFactory no asigne un vehículo si la FK está en Vehiculo.
            $chofer = Chofer::factory()->create();
            // Luego, crea un vehículo y asócialo a este nuevo chofer.
            // Esto es crucial para la consistencia de la relación 1:1.
            Vehiculo::factory()->create([
                'chofer_id' => $chofer->id,
                'empresa_id' => $empresa->id, // Usar la misma empresa o una aleatoria
                'tipo_combustible_id' => $tipoCombustible->id, // Usar el mismo tipo de combustible o uno aleatorio
            ]);
            $chofer->load('vehiculo'); // Recargar la relación para que el chofer tenga su vehículo
        }

        // Definir un saldo inicial de cantidad y monetario para la tarjeta
        $initialQuantity = $this->faker->randomFloat(2, 50, 300);
        $initialMonetaryBalance = round($initialQuantity * ($tipoCombustible->precio ?? $this->faker->randomFloat(2, 10, 30)), 2);


        return [
            'numero' => $this->faker->unique()->randomNumber(8),
            'saldo_monetario_actual' => $initialMonetaryBalance, // Saldo monetario inicial
            'cantidad_actual' => $initialQuantity,             // Cantidad de combustible inicial
            'saldo_maximo' => $this->faker->randomFloat(2, 500, 5000),
            'limite_consumo_mensual' => $this->faker->randomFloat(2, 100, 1000),
            'tipo_combustible_id' => $tipoCombustible->id,
            'fecha_vencimiento' => $this->faker->dateTimeBetween('now', '+5 years')->format('Y-m-d'),
            // 'vehiculo_id' ya no se asigna directamente aquí, se obtiene a través del chofer
            // 'vehiculo_id' => $this->faker->boolean(80) ? ($vehiculo ? $vehiculo->id : null) : null, // ¡ELIMINADO!
            'empresa_id' => $empresa->id,
            'activa' => $this->faker->boolean(),
            'chofer_id' => $chofer->id, // Asignar el chofer que tiene un vehículo
        ];
    }

    /**
     * Indicate that the card has a specific maximum balance.
     *
     * @param float $saldo
     * @return static
     */
    public function withMaxBalance(float $saldo): static
    {
        return $this->state(fn (array $attributes) => [
            'saldo_maximo' => $saldo,
        ]);
    }

    /**
     * Indicate that the card has a specific monthly consumption limit.
     *
     * @param float $limit
     * @return static
     */
    public function withMonthlyLimit(float $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'limite_consumo_mensual' => $limit,
        ]);
    }
}

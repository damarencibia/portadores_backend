<?php

namespace Database\Factories;

use App\Models\RetiroCombustible;
// use App\Models\TipoCombustible; // Eliminado: Se infiere de la tarjeta
use App\Models\User;
use App\Models\TarjetaCombustible;
// use App\Models\Vehiculo; // Eliminado: Se infiere de Tarjeta -> Chofer -> Vehiculo
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RetiroCombustible>
 */
class RetiroCombustibleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RetiroCombustible::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Asegurarse de que haya al menos un Usuario y TarjetaCombustible creados.
        // La tarjeta debe tener un chofer, y el chofer un vehículo, para la consistencia del reporte.
        $registrador = User::inRandomOrder()->first() ?? User::factory()->create();
        $validador = User::inRandomOrder()->first();

        // Intentar obtener una tarjeta que tenga un chofer y un vehículo asociado.
        // Si no existe, crear una que lo tenga (esto puede ser complejo si los factories no están en orden).
        // Para un seeder simple, asumimos que TarjetaCombustibleFactory ya crea esto.
        // Es crucial que la tarjeta cargue su relación 'tipoCombustible' para obtener el precio.
        $tarjeta = TarjetaCombustible::with('tipoCombustible')->inRandomOrder()->first() ?? TarjetaCombustible::factory()->create();

        // El tipo de combustible y el vehículo se infieren de la tarjeta, no se asignan directamente aquí.
        // $tipoCombustible = TipoCombustible::inRandomOrder()->first() ?? TipoCombustible::factory()->create();
        // $vehiculo = Vehiculo::inRandomOrder()->first() ?? Vehiculo::factory()->create(); // Asegurar que haya un vehículo

        $cantidad = $this->faker->randomFloat(2, 5, 50);
        // Para calcular el importe, necesitamos el precio del tipo de combustible de la tarjeta.
        // Si la tarjeta no tiene un tipo de combustible o precio, usaremos un precio simulado.
        $precioUnitarioSimulado = $tarjeta->tipoCombustible->precio ?? $this->faker->randomFloat(2, 5, 15);


        return [
            'fecha' => $this->faker->date(),
            'hora' => $this->faker->time('H:i:s'),
            'tarjeta_combustible_id' => $tarjeta->id,
            // 'tipo_combustible_id' => $tipoCombustible->id, // ¡ELIMINADO! Se infiere de la tarjeta
            // 'vehiculo_id' => $vehiculo->id, // ¡ELIMINADO! Se infiere de Tarjeta -> Chofer -> Vehiculo
            'cantidad' => $cantidad,
            'importe' => round($cantidad * $precioUnitarioSimulado, 2),
            'odometro' => $this->faker->numberBetween(1000, 500000),
            'lugar' => $this->faker->city(),
            'no_chip' => $this->faker->boolean(50) ? $this->faker->unique()->numerify('CHIP-########') : null,
                'motivo' => $this->faker->sentence(),
            'registrado_por_id' => $registrador->id,
            'validado_por_id' => $this->faker->boolean(70) ? ($validador ? $validador->id : null) : null,
            'fecha_validacion' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'estado' => $this->faker->randomElement(['Pendiente', 'Validado', 'Rechazado']),
        ];
    }
}

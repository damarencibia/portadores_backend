<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empresa;
use App\Models\TipoCombustible;
use App\Models\Vehiculo;
use App\Models\TarjetaCombustible;
use App\Models\CargaCombustible;
use App\Models\RetiroCombustible;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Recommended order for seeding due to foreign key dependencies:

        // 1. Basic entities without many dependencies
        User::factory(10)->create(); // Create 10 users
        Empresa::factory(5)->create(); // Create 5 companies
        TipoCombustible::factory(3)->create(); // Create 3 fuel types

        // 2. Entities with dependencies on basic entities
        // Note: VehiculoFactory now creates a Chofer for each vehicle, and ChoferFactory creates an Empresa.
        // Ensure ChoferFactory doesn't create a vehicle directly if the FK is in Vehiculo.
        Vehiculo::factory(15)->create(); // Create 15 vehicles

        // 3. TarjetaCombustible (depends on TipoCombustible, Empresa, Chofer, and indirectly Vehiculo)
        // Create 20 fuel cards, some with a specific max balance and monthly limit for testing
        // TarjetaCombustibleFactory now ensures the assigned chofer has a vehicle.
        TarjetaCombustible::factory(15)->create();
        TarjetaCombustible::factory(5)
            ->withMaxBalance(1500.00)
            ->withMonthlyLimit(300.00)
            ->create();

        // 4. CargaCombustible (depends on User, TarjetaCombustible)
        // Create 50 fuel charges
        CargaCombustible::factory(50)->create();

        // 5. RetiroCombustible (depends on User, TarjetaCombustible)
        // Create 30 fuel withdrawals
        RetiroCombustible::factory(30)->create();

        // Optional: Update TarjetaCombustible balances based on created Cargas and Retiros
        // This is a more complex operation that simulates the transaction history
        $this->command->info('Updating TarjetaCombustible balances based on generated Cargas and Retiros...');
        foreach (TarjetaCombustible::all() as $tarjeta) {
            $totalCargasCantidad = CargaCombustible::where('tarjeta_combustible_id', $tarjeta->id)->sum('cantidad');
            $totalCargasImporte = CargaCombustible::where('tarjeta_combustible_id', $tarjeta->id)->sum('importe');
            $totalRetirosCantidad = RetiroCombustible::where('tarjeta_combustible_id', $tarjeta->id)->sum('cantidad');
            $totalRetirosImporte = RetiroCombustible::where('tarjeta_combustible_id', $tarjeta->id)->sum('importe');

            // ¡CORRECCIÓN! Usar los nuevos nombres de columna para actualizar los saldos
            $tarjeta->cantidad_actual = $totalCargasCantidad - $totalRetirosCantidad;
            $tarjeta->saldo_monetario_actual = $totalCargasImporte - $totalRetirosImporte;
            $tarjeta->save();
        }
        $this->command->info('Finished updating TarjetaCombustible balances.');
    }
}

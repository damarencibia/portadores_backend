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
    public function run(): void
    {
        // Crear 5 empresas
        Empresa::factory(5)->create();

        // Crear 10 usuarios con roles válidos
        $roles = ['operador', 'supervisor', 'admin'];
        Empresa::all()->each(function ($empresa) use ($roles) {
            User::factory()->count(2)->create([
                'empresa_id' => $empresa->id,
                'roles' => fake()->randomElement($roles),
            ]);
        });

        // Crear tipos de combustible
        TipoCombustible::factory(3)->create();

        // Crear vehículos (puede requerir chofer y empresa según tu modelo)
        Vehiculo::factory(15)->create();

        // Crear tarjetas de combustible
        TarjetaCombustible::factory(15)->create();
        TarjetaCombustible::factory(5)
            ->withMaxBalance(1500.00)
            ->withMonthlyLimit(300.00)
            ->create();

        // // Crear cargas
        // CargaCombustible::factory(50)->create();

        // // Crear retiros
        // RetiroCombustible::factory(30)->create();

        // // Actualizar saldos
        // $this->command->info('Actualizando saldos de Tarjetas...');
        // foreach (TarjetaCombustible::all() as $tarjeta) {
        //     $totalCargasCantidad = CargaCombustible::where('tarjeta_combustible_id', $tarjeta->id)->sum('cantidad');
        //     $totalCargasImporte = CargaCombustible::where('tarjeta_combustible_id', $tarjeta->id)->sum('importe');
        //     $totalRetirosCantidad = RetiroCombustible::where('tarjeta_combustible_id', $tarjeta->id)->sum('cantidad');
        //     $totalRetirosImporte = RetiroCombustible::where('tarjeta_combustible_id', $tarjeta->id)->sum('importe');

        //     $tarjeta->cantidad_actual = $totalCargasCantidad - $totalRetirosCantidad;
        //     $tarjeta->saldo_monetario_actual = $totalCargasImporte - $totalRetirosImporte;
        //     $tarjeta->save();
        // }
        // $this->command->info('Saldos actualizados.');
    }
}

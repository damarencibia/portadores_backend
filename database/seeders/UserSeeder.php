<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurar que hay empresas
        $empresas = Empresa::all();
        if ($empresas->isEmpty()) {
            $this->call(EmpresaSeeder::class);
            $empresas = Empresa::all();
        }

        // Crear usuario admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'roles' => 'admin',
            'empresa_id' => $empresas->random()->id,
        ]);

        // Crear usuarios por empresa
        foreach ($empresas as $empresa) {
            // Supervisor
            User::factory()->create([
                'empresa_id' => $empresa->id,
                'roles' => 'supervisor',
            ]);

            // Operadores
            User::factory()->count(3)->create([
                'empresa_id' => $empresa->id,
                'roles' => 'operador',
            ]);

            // Otros usuarios con roles aleatorios
            User::factory()->count(2)->create([
                'empresa_id' => $empresa->id,
            ]);
        }
    }
}


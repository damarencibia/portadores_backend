<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Empresa; // Importar Empresa
use App\Models\Role; // Importar Role
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurarse de que haya empresas y Roles creados
        $empresas = Empresa::all();
        $roles = Role::all();

        if ($empresas->isEmpty()) {
            $this->call(empresaSeeder::class);
            $empresas = Empresa::all();
        }

        if ($roles->isEmpty()) {
             $this->call(RoleSeeder::class);
             $roles = Role::all();
        }


        // Crea un usuario administrador
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'empresa_id' => $empresas->random()->id, // Asigna a una Empresa aleatoria
        ]);
        $adminRole = $roles->where('name', 'admin')->first();
        if ($adminRole) {
             $adminUser->roles()->attach($adminRole);
        }


        // Crea usuarios para cada Empresa y asigna roles
        $empresas->each(function ($Empresa) use ($roles) {
            // Crea un responsable por Empresa
            $responsable = User::factory()->create([
                'empresa_id' => $Empresa->id,
            ]);
            $responsableRole = $roles->where('name', 'responsable_Empresa')->first();
             if ($responsableRole) {
                $responsable->roles()->attach($responsableRole);
             }


            // Crea algunos operadores de datos por Empresa
            User::factory()->count(3)->create([
                'empresa_id' => $Empresa->id,
            ])->each(function ($user) use ($roles) {
                $operadorRole = $roles->where('name', 'operador_datos')->first();
                 if ($operadorRole) {
                    $user->roles()->attach($operadorRole);
                 }
            });

             // Crea algunos usuarios de consulta por Empresa
            User::factory()->count(2)->create([
                'empresa_id' => $Empresa->id,
            ])->each(function ($user) use ($roles) {
                $consultaRole = $roles->where('name', 'consulta')->first();
                 if ($consultaRole) {
                    $user->roles()->attach($consultaRole);
                 }
            });
        });

        // Crea algunos usuarios sin rol específico inicialmente
        User::factory()->count(10)->create([
             'empresa_id' => $empresas->random()->id,
        ]);
    }
}


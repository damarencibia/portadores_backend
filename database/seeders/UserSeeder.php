<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Ueb; // Importar Ueb
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
        // Asegurarse de que haya UEBs y Roles creados
        $uebs = Ueb::all();
        $roles = Role::all();

        if ($uebs->isEmpty()) {
            $this->call(UebSeeder::class);
            $uebs = Ueb::all();
        }

        if ($roles->isEmpty()) {
             $this->call(RoleSeeder::class);
             $roles = Role::all();
        }


        // Crea un usuario administrador
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'ueb_id' => $uebs->random()->id, // Asigna a una UEB aleatoria
        ]);
        $adminRole = $roles->where('name', 'admin')->first();
        if ($adminRole) {
             $adminUser->roles()->attach($adminRole);
        }


        // Crea usuarios para cada UEB y asigna roles
        $uebs->each(function ($ueb) use ($roles) {
            // Crea un responsable por UEB
            $responsable = User::factory()->create([
                'ueb_id' => $ueb->id,
            ]);
            $responsableRole = $roles->where('name', 'responsable_ueb')->first();
             if ($responsableRole) {
                $responsable->roles()->attach($responsableRole);
             }


            // Crea algunos operadores de datos por UEB
            User::factory()->count(3)->create([
                'ueb_id' => $ueb->id,
            ])->each(function ($user) use ($roles) {
                $operadorRole = $roles->where('name', 'operador_datos')->first();
                 if ($operadorRole) {
                    $user->roles()->attach($operadorRole);
                 }
            });

             // Crea algunos usuarios de consulta por UEB
            User::factory()->count(2)->create([
                'ueb_id' => $ueb->id,
            ])->each(function ($user) use ($roles) {
                $consultaRole = $roles->where('name', 'consulta')->first();
                 if ($consultaRole) {
                    $user->roles()->attach($consultaRole);
                 }
            });
        });

        // Crea algunos usuarios sin rol específico inicialmente
        User::factory()->count(10)->create([
             'ueb_id' => $uebs->random()->id,
        ]);
    }
}


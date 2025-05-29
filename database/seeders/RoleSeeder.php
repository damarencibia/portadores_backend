<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crea los roles básicos
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'responsable_ueb']);
        Role::create(['name' => 'operador_datos']);
        Role::create(['name' => 'consulta']);

        // O crea roles aleatorios usando el factory
        // Role::factory()->count(4)->create();
    }
}


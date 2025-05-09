<?php

// database/seeders/RoleSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Crear roles iniciales
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'client']);
    }
}

<?php

// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Obtener los roles
        $adminRole = Role::where('name', 'admin')->first();
        $clientRole = Role::where('name', 'client')->first();

        // Crear un usuario admin
        $admin = User::create([
            'name' => 'Admin User',
            'lastname' => 'Admin Lastname',
            'ci' => '1234567890',
            'address' => 'Admin Address',
            'email' => 'admin@example.com',
            'phone' => '1234567890',
            // 'password' => bcrypt('password'), // Descomenta si tienes la columna password
        ]);
        $admin->roles()->attach($adminRole); // Asignar rol de admin

        // Crear varios usuarios client
        User::factory()->count(10)->create()->each(function ($user) use ($clientRole) {
            $user->roles()->attach($clientRole); // Asignar rol de client
        });
    }
}

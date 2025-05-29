<?php

namespace Database\Seeders;

use App\Models\Ueb;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UebSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crea 5 UEBs usando el factory
        Ueb::factory()->count(5)->create();
    }
}


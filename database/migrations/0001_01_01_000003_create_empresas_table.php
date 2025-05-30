<?php

// database/migrations/xxxx_create_empresas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedInteger('code')->unique();
            $table->string('direccion');
            $table->timestamps();
            // $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('empresas');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tipo_combustibles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('unidad_medida');
            $table->decimal('precio', 10, 2);
            $table->timestamps();
            // $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tipo_combustibles');
    }
};
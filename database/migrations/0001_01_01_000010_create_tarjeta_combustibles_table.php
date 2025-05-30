<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tarjeta_combustibles', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('tipo_combustible_id')->constrained('tipo_combustibles')->onDelete('cascade');;
            $table->date('fecha_vencimiento');
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehiculos')->onDelete('cascade');;
            // $table->foreignId('empresa_id')->constrained('emrpesas');
            $table->boolean('activa')->default(true);
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            // $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tarjeta_combustibles');
    }
};
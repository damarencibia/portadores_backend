<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_interno')->unique()->nullable();
            $table->string('marca');
            $table->string('modelo');
            $table->string('tipo_vehiculo')->nullable();
            $table->integer('ano');
            $table->foreignId('tipo_combustible_id')->constrained('tipo_combustibles')->onDelete('restrict');
            $table->decimal('indice_consumo', 10, 2);
            $table->decimal('prueba_litro', 10, 2);
            $table->date('ficav');
            $table->integer('capacidad_tanque');
            $table->string('color');
            $table->string('chapa')->unique();
            $table->string('numero_motor')->unique();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('numero_chasis')->unique();
            $table->string('estado_tecnico');
            $table->foreignId('chofer_id')->nullable()->unique()->constrained('choferes')->onDelete('set null'); // Nuevo: FK a choferes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};

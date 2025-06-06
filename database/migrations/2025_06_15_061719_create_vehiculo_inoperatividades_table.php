<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea la tabla 'vehiculo_inoperatividades'.
     */
    public function up(): void
    {
        Schema::create('vehiculo_inoperatividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('cascade'); // Relación con el vehículo
            $table->date('fecha_salida_servicio'); // Fecha en que salió de servicio
            $table->date('fecha_reanudacion_servicio')->nullable(); // Fecha en que volvió (null si sigue fuera)
            $table->string('motivo_averia'); // Descripción del motivo por el que salió de servicio
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     * Elimina la tabla 'vehiculo_inoperatividades'.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculo_inoperatividades');
    }
};
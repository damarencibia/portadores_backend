<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea la tabla 'vehiculos'.
     */
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id(); // Columna ID autoincremental
            $table->string('numero_interno')->unique()->nullable(); // Número interno del vehículo, opcional y único
            $table->string('marca'); // Marca del vehículo
            $table->string('modelo'); // Modelo del vehículo
            $table->integer('ano'); // Año de fabricación
            $table->foreignId('tipo_combustible_id')->constrained('tipo_combustibles')->onDelete('restrict'); // Clave foránea a tipo_combustibles
            $table->decimal('indice_consumo', 10, 2); // Índice de consumo (ej: L/100km)
            $table->decimal('prueba_litro', 10, 2); // Prueba por litro (ej: km/L)
            $table->boolean('ficav'); // FICAV (boolean según tu migración)
            $table->integer('capacidad_tanque'); // Capacidad del tanque
            $table->string('color'); // Color del vehículo
            $table->string('chapa')->unique(); // Número de matrícula (chapa), único
            $table->string('numero_motor')->unique(); // Número de motor único
            $table->boolean('activo')->default(true); // Indica si el vehículo está activo
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade'); // Clave foránea a la tabla 'uebs'
            $table->string('numero_chasis')->unique(); // Número de chasis único
            $table->string('estado_tecnico'); // Estado técnico
            // Clave foránea a usuario, puede ser nulo si no hay un usuario asignado permanentemente
            // Eliminamos ->unique() para permitir que un usuario esté asociado a múltiples vehículos
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps(); // Columnas created_at y updated_at
            // $table->softDeletes(); // Añade la columna 'deleted_at' para Soft Deletes
        });
    }

    /**
     * Reverse the migrations.
     * Elimina la tabla 'vehiculos'.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};

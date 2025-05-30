<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea la tabla 'carga_combustibles'.
     */
    public function up(): void
    {
        Schema::create('carga_combustibles', function (Blueprint $table) {
            $table->id(); // Columna ID autoincremental
            $table->date('fecha'); // Fecha de la carga
            $table->time('hora')->nullable(); // Hora de la carga, opcional
            // Añadir la clave foránea para tipo_combustible_id
            $table->foreignId('tipo_combustible_id')->constrained('tipo_combustibles')->onDelete('restrict');
            $table->decimal('cantidad', 10, 2); // Cantidad de combustible cargado
            $table->integer('odometro'); // Lectura del odómetro al momento de la carga
            $table->string('lugar')->nullable(); // Lugar donde se realizó la carga, opcional
            $table->string('numero_tarjeta')->nullable(); // Número de tarjeta (puede ser redundante si se usa la FK tarjeta_combustible_id)
            $table->string('no_chip')->nullable(); // Número de chip, opcional
            $table->foreignId('registrado_por_id')->constrained('users')->onDelete('restrict'); // Clave foránea al usuario que registró
            // Clave foránea al usuario que validó, puede ser nulo si aún no se valida
            $table->foreignId('validado_por_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_validacion')->nullable(); // Fecha y hora de validación, nulo si no validado
            $table->string('estado')->default('Pendiente'); // Estado de la carga (ej: pendiente, validada, rechazada)
            $table->decimal('importe', 10, 2)->nullable(); // Importe de la carga, opcional
            // Clave foránea a la tarjeta de combustible usada
            $table->foreignId('tarjeta_combustible_id')->nullable()->constrained('tarjeta_combustibles')->onDelete('restrict');
            $table->timestamps(); // Columnas created_at y updated_at
            // $table->softDeletes(); // Añade la columna 'deleted_at' para Soft Deletes

            // Consideración: También podrías querer una FK a Vehiculo aquí, ya que una carga está asociada a un vehículo específico.
            // Si la FK a TarjetaCombustible ya implica el vehículo, podría ser suficiente, pero a veces es útil tener la FK directa.
            // $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     * Elimina la tabla 'carga_combustibles'.
     */
    public function down(): void
    {
        Schema::dropIfExists('carga_combustibles');
    }
};

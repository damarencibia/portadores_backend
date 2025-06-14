<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea la tabla 'retiro_combustibles' (solo para salidas/consumos).
     */
    public function up(): void
    {
        Schema::create('retiro_combustibles', function (Blueprint $table) {
            $table->id();
            $table->date('fecha'); // Fecha del retiro
            $table->time('hora')->nullable(); // Hora del retiro, opcional

            $table->foreignId('tarjeta_combustible_id')->constrained('tarjeta_combustibles')->onDelete('restrict');
            $table->decimal('cantidad', 10, 2); // Cantidad de combustible RETIRADO/CONSUMIDO
            $table->decimal('importe', 10, 2); // Importe del retiro (cantidad * precio)

            $table->decimal('cantidad_combustible_anterior', 10, 2)->nullable();
            $table->decimal('cantidad_combustible_al_momento_retiro', 10, 2)->nullable();

            $table->integer('odometro');
            $table->string('lugar')->nullable(); // Lugar donde se realizó el retiro, opcional
            $table->string('motivo')->nullable(); // Motivo por el que se realizó la carga
            $table->string('no_chip')->nullable(); // Número de chip/transacción, opcional (si aplica a retiros)

            $table->foreignId('registrado_por_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('validado_por_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_validacion')->nullable();
            $table->string('estado')->default('pendiente'); // Uniformidad con 'carga_combustibles'
            $table->string('motivo_rechazo')->nullable(); // Añadido: Para guardar el motivo de rechazo

            $table->timestamps();
            $table->softDeletes(); // Añadido: Para Soft Deletes

            $table->string('deletion_reason')->nullable(); // Añadido: Motivo de la eliminación lógica
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retiro_combustibles');
    }
};
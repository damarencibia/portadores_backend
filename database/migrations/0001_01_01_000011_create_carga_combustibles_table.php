<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea la tabla 'carga_combustibles' (solo para entradas/recargas).
     */
    public function up(): void
    {
        Schema::create('carga_combustibles', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->time('hora')->nullable();

            $table->foreignId('tarjeta_combustible_id')->constrained('tarjeta_combustibles')->onDelete('restrict');
            $table->decimal('cantidad', 10, 2); // Cantidad de combustible CARGADO
            $table->decimal('importe', 10, 2); // Importe de la carga (cantidad * precio)

            $table->decimal('saldo_monetario_anterior', 10, 2)->nullable();
            $table->decimal('cantidad_combustible_anterior', 10, 2)->nullable();

            $table->decimal('saldo_monetario_al_momento_carga', 10, 2)->nullable();
            $table->decimal('cantidad_combustible_al_momento_carga', 10, 2)->nullable();


            $table->integer('odometro')->nullable();
            $table->string('lugar')->nullable(); // Lugar donde se realizó la carga, opcional
            $table->string('motivo')->nullable(); // Motivo por el que se realizó la carga

            $table->string('no_chip')->nullable(); // Número de chip/transacción, opcional

            $table->foreignId('registrado_por_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('validado_por_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_validacion')->nullable();
            $table->string('estado')->default('Pendiente');

            $table->timestamps();
            // $table->softDeletes(); // Descomentar si usas Soft Deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carga_combustibles');
    }
};

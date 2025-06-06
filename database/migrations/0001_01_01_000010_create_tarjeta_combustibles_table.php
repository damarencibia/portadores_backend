<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarjeta_combustibles', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->decimal('saldo_monetario_actual', 10, 2)->default(0.00); // Saldo actual monetario
            $table->decimal('cantidad_actual', 10, 2)->default(0.00);       // Cantidad de combustible actual
            $table->decimal('saldo_maximo', 10, 2)->nullable(); // Límite máximo de carga (asumido en cantidad)
            $table->decimal('limite_consumo_mensual', 10, 2)->nullable(); // Límite de consumo mensual (asumido en cantidad)
            $table->decimal('consumo_cantidad_mensual_acumulado', 10, 2)->default(0.00); // NUEVO: Acumulado de consumo de cantidad en el mes

            $table->foreignId('tipo_combustible_id')->constrained('tipo_combustibles')->onDelete('restrict');
            $table->date('fecha_vencimiento');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('restrict');
            $table->boolean('activa')->default(true);
            $table->foreignId('chofer_id')->constrained('choferes')->onDelete('restrict'); // FK a choferes
            $table->timestamps();
            // $table->softDeletes(); // Descomentar si usas Soft Deletes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarjeta_combustibles');
    }
};

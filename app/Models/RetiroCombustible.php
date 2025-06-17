<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetiroCombustible extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'accessed',
        'fecha',
        'hora',
        'tarjeta_combustible_id',
        'cantidad',
        'importe',
        'cantidad_combustible_anterior',
        'cantidad_combustible_al_momento_retiro',
        'odometro',
        'lugar',
        'motivo',
        'no_chip',
        'registrado_por_id',
        'validado_por_id',
        'fecha_validacion',
        'estado',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'importe' => 'decimal:2',
        'cantidad_combustible_anterior' => 'decimal:2',
        'cantidad_combustible_al_momento_retiro' => 'decimal:2',
    ];

    /**
     * Get the fuel card that the withdrawal belongs to.
     */
    public function tarjetaCombustible(): BelongsTo
    {
        return $this->belongsTo(TarjetaCombustible::class);
    }

    /**
     * Get the user who registered the withdrawal.
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    /**
     * Get the user who validated the withdrawal.
     */
    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por_id');
    }
}

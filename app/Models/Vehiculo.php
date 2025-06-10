<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehiculo extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_interno',
        'marca',
        'modelo',
        'tipo_vehiculo',
        'ano',
        'tipo_combustible_id',
        'indice_consumo',
        'prueba_litro',
        'ficav',
        'capacidad_tanque',
        'color',
        'chapa',
        'numero_motor',
        'empresa_id',
        'numero_chasis',
        'estado_tecnico',
        'chofer_id',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class);
    }

    public function inoperatividades(): HasMany
    {
        return $this->hasMany(VehiculoInoperatividad::class);
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class);
    }

    // ✅ Acceso indirecto a las tarjetas
    public function getTarjetasCombustibleAttribute()
    {
        return $this->chofer ? $this->chofer->tarjetasCombustible : collect();
    }
}


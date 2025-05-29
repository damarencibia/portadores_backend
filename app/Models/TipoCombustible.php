<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoCombustible extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'unidad_medida',
        'precio'
    ];

    public function tarjetasCombustible()
    {
        return $this->hasMany(TarjetaCombustible::class);
    }

    public function cargasCombustible()
    {
        return $this->hasMany(CargaCombustible::class);
    }

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class);
    }
}

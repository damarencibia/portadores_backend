<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargaCombustible extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'hora',
        'cantidad',
        'odometro',
        'lugar',
        'registrado_por_id',
        'validado_por_id',
        'fecha_validacion',
        'estado',
        'importe',
        'tarjeta_combustible_id'
    ];

    // Relación con User (quien registró la carga)
    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

     // Relación con User (quien validó la carga)
     public function validadoPor()
     {
         return $this->belongsTo(User::class, 'validado_por_id')->withDefault();
     }

      // Relación con TarjetaCombustible
    public function tarjetaCombustible()
    {
        return $this->belongsTo(TarjetaCombustible::class);
    }

    public function tipoCombustible()
    {
        return $this->belongsTo(TipoCombustible::class);
    }
    
}

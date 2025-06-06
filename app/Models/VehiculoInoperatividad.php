<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehiculoInoperatividad extends Model
{
    use HasFactory;

    protected $table = 'vehiculo_inoperatividades'; // Asegura que apunte a la tabla correcta

    protected $fillable = [
        'vehiculo_id',
        'fecha_salida_servicio',
        'fecha_reanudacion_servicio',
        'motivo_averia',
    ];

    protected $casts = [
        'fecha_salida_servicio' => 'datetime',
        'fecha_reanudacion_servicio' => 'datetime',
    ];

    /**
     * Get the Vehiculo that the inoperatividad belongs to.
     */
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
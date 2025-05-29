<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TarjetaCombustible extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'saldo',
        'fecha_vencimiento',
        'tipo_combustible_id',
        'vehiculo_id',
        'ueb_id',
        'activa'
    ];

    public function ueb()
    {
        return $this->belongsTo(Ueb::class);
    }

    public function tipoCombustible()
    {
        return $this->belongsTo(TipoCombustible::class);
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function cargasCombustible()
    {
        return $this->hasMany(CargaCombustible::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class); // Relación belongsTo con User
    }

}
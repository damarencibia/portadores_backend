<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes; // ¡ELIMINADO O COMENTADO!

class TarjetaCombustible extends Model
{
    use HasFactory;
    // use SoftDeletes; // Esta línea debe estar comentada o eliminada si no usas Soft Deletes

    protected $fillable = [
        'numero',
        'saldo_monetario_actual', //cantidad de dinero actual que dispone la tarjeta de combustible
        'cantidad_actual', //cantidad de combustible actual que dispone la tarjeta
        'saldo_maximo', //limite de saldo que puede tener la tarjeta 
        'limite_consumo_mensual', //limite del consumo acumulado que puede tener la tarjeta de combustible
        'consumo_cantidad_mensual_acumulado', //contador que lleva la cantidad de saldo "consumido" por la tarejta durante el mes. No puede exceder el 'limite_consumo_mensual'
        'fecha_vencimiento', //fecha de vencimiento de la tarjeta de combustible
        'tipo_combustible_id', //id del tipo de combustible asociado a la tarjeta
        'empresa_id', //id de la empresa que esta asociada a la tarjeta
        'activa', //indicador de la tarjeta que dice si está o no operativa
        'chofer_id', //id del chofer que está asociado a la tarjeta
    ];

    protected $casts = [
        'activa' => 'boolean',
        'saldo_monetario_actual' => 'decimal:2',
        'cantidad_actual' => 'decimal:2',
        'saldo_maximo' => 'decimal:2',
        'limite_consumo_mensual' => 'decimal:2',
        'consumo_cantidad_mensual_acumulado' => 'decimal:2',
    ];

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tipoCombustible()
    {
        return $this->belongsTo(TipoCombustible::class);
    }

    public function cargas()
    {
        return $this->hasMany(CargaCombustible::class);
    }

    public function retiros()
    {
        return $this->hasMany(RetiroCombustible::class);
    }

    public function chofer()
    {
        return $this->belongsTo(Chofer::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'code',
        'direccion'
    ];

    protected $casts = [
        'code' => 'integer'
    ];

    // Relación uno a muchos con usuarios
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class);
    }

    // public function tarjetasCombustible()
    // {
    //     return $this->hasMany(TarjetaCombustible::class);
    // }

    protected static function generateAutoIncrementCode()
    {
        $lastProduct = self::latest('code')->first();
        return $lastProduct ? $lastProduct->code + 1 : 1000;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->code)) {
                $product->code = self::generateAutoIncrementCode();
            }
        });
    }
}
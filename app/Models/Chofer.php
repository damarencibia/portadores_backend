<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Chofer extends Model
{
    use HasFactory;

    protected $table = 'choferes';

    protected $fillable = [
        'nombre',
        'apellidos',
        'email',
        'empresa_id',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Get the vehicle associated with the chofer.
     * Based on "Cada chofer tiene asociado un solo vehiculo y viceversa",
     * the foreign key `chofer_id` is on the `vehiculos` table.
     */
    public function vehiculo(): HasOne
    {
        // Un chofer tiene un vehículo, y la clave foránea (chofer_id) está en la tabla 'vehiculos'.
        return $this->hasOne(Vehiculo::class); // Laravel inferirá 'chofer_id' en el modelo Vehiculo
    }

    /**
     * Get the fuel cards for the chofer.
     */
    public function tarjetasCombustible(): HasMany
    {
        return $this->hasMany(TarjetaCombustible::class, 'chofer_id');
    }
}

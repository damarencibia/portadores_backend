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
        'chofer_id', // Asegúrate de que 'chofer_id' está en fillable
    ];

    /**
     * Get the company that owns the vehicle.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Get the fuel type that the vehicle uses.
     */
    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class);
    }

    /**
     * Get the fuel cards for the vehicle.
     * (Note: This relationship might be less direct if cards are primarily linked via Chofer)
     */
    public function tarjetasCombustible(): HasMany
    {
        // This relationship assumes a direct link between Vehiculo and TarjetaCombustible.
        // If TarjetaCombustible is only linked to Chofer, and Chofer to Vehiculo,
        // then this relation might not be strictly necessary or might need a 'hasManyThrough'.
        // For now, keeping it as it was in your previous code.
        return $this->hasMany(TarjetaCombustible::class);
    }

    /**
     * Get the inoperabilities for the vehicle.
     */
    public function inoperatividades(): HasMany
    {
        return $this->hasMany(VehiculoInoperatividad::class);
    }

    /**
     * Get the chofer that owns the vehicle.
     * Based on the logic "Cada chofer tiene asociado un solo vehiculo y viceversa",
     * the foreign key `chofer_id` is on the `vehiculos` table.
     */
    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Chofer::class); // Laravel will infer 'chofer_id'
    }
}

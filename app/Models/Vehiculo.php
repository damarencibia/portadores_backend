<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Importar SoftDeletes

class Vehiculo extends Model
{
    use HasFactory; // Usar SoftDeletes trait

    protected $fillable = [
        'numero_interno', // Corregido según migraciones
        'marca',
        'modelo',
        'ano', // Corregido según migraciones
        'tipo_combustible_id',
        'indice_consumo', // Añadido según migraciones
        'prueba_litro', // Añadido según migraciones
        'ficav', // Añadido según migraciones
        'capacidad_tanque',
        'color', // Añadido según migraciones
        'chapa', // Corregido según migraciones
        'numero_motor', // Añadido según migraciones
        'activo',
        'ueb_id',
        'numero_chasis', // Añadido según migraciones
        'estado_tecnico', // Añadido según migraciones
        'user_id', // Añadido según migraciones
    ];

    /**
     * Get the UEB that the Vehiculo belongs to.
     */
    public function ueb()
    {
        return $this->belongsTo(Ueb::class);
    }

    /**
     * Get the TipoCombustible for the Vehiculo.
     */
    public function tipoCombustible()
    {
        return $this->belongsTo(TipoCombustible::class);
    }

    /**
     * Get the TarjetaCombustibles for the Vehiculo.
     */
    public function tarjetasCombustible()
    {
        return $this->hasMany(TarjetaCombustible::class);
    }

    /**
     * Get the User that is assigned to the Vehiculo (nullable).
     */
    public function user()
    {
        return $this->belongsTo(User::class); // Relación belongsTo con User
    }

    // Opcional: Si agregaste vehiculo_id a carga_combustibles
    // /**
    //  * Get the CargaCombustibles for the Vehiculo.
    //  */
    // public function cargasCombustible()
    // {
    //     return $this->hasMany(CargaCombustible::class);
    // }
}

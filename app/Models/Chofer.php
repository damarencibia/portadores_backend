<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chofer extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'choferes'; // Especifica el nombre correcto de la tabla

    protected $fillable = [
        'nombre',
        'apellidos',
        'email',
        'empresa_id', // Cambiado de 'ueb_id' para coincidir con la migración y factory
    ];

    // Relación con Empresa (asumiendo que existe un modelo Empresa)
    public function empresa()
    {
        // Asegúrate de que la clave foránea aquí coincida con tu migración.
        // Si en la migración es 'empresa_id', aquí también debería serlo (o Laravel lo infiere correctamente).
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}

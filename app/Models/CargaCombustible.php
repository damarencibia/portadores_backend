<?php
/**La carga de combustible describe el proceso en el que un vehiculo reposta combustible
 * y se descuenta el importe de la tarjeta de combustible asociada a la carga.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CargaCombustible extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'accessed',
        'fecha', //fecha en que se ralizó la carga de combustible
        'hora', //hora en que se ralizó la carga de combustible
        'tarjeta_combustible_id', //tarjeta de combustible que será recargada con combustible y se le descontará su saldo prinicpal
        'cantidad', //cantidad de combustible que se le agregará a la tarjeta de combustible
        'importe', // costo total o importe de la carga de combustible
        'odometro', //lectura de odómetro al momento de repostar combustible
        'lugar', //lugar donde se ralizó la carga de combustible
        'motivo', //motivo por el que se relizó la carga de combustible
        'no_chip',
        'registrado_por_id',
        'validado_por_id',
        'fecha_validacion',
        'estado',
        'motivo_rechazo',
        'deletion_reason',
        'deleted_at'
        // 'saldo_monetario_al_momento_carga' y 'cantidad_combustible_al_momento_carga'
        // se manejan directamente en el controlador, no están en fillable.
        // Los nuevos campos 'saldo_monetario_anterior' y 'cantidad_combustible_anterior'
        // tampoco están en fillable, se asignan directamente.
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'importe' => 'decimal:2',
        // Casts para los saldos históricos (anterior y al momento de carga)
        'saldo_monetario_anterior' => 'decimal:2',
        'cantidad_combustible_anterior' => 'decimal:2',
        'saldo_monetario_al_momento_carga' => 'decimal:2',
        'cantidad_combustible_al_momento_carga' => 'decimal:2',
    ];

    /**
     * Get the fuel card that the charge belongs to.
     */
    public function tarjetaCombustible(): BelongsTo
    {
        return $this->belongsTo(TarjetaCombustible::class);
    }

    /**
     * Get the user who registered the charge.
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    /**
     * Get the user who validated the charge.
     */ 
    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por_id');
    }
}


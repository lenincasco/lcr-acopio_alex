<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\PrestamoHelper;
use Log;
use Illuminate\Validation\ValidationException;

class Abono extends Model
{
    protected $table = 'abonos';

    protected $fillable = [
        'prestamo_id',
        'fecha_pago',
        'abono_capital',
        'intereses',
        'observaciones',
        'qq_abonados',
        'dias_diff',
    ];

    // Relación: un abono pertenece a un préstamo
    public function prestamo()
    {
        return $this->belongsTo(Prestamo::class);
    }

    protected static function booted()
    {
        // Cuando se crea una venta, se reduce el inventario.
        static::created(function ($abono) {
            PrestamoHelper::updateOnCreate($abono);
        });

        // En una actualización, se ajusta el inventario según la diferencia entre
        // los valores nuevos y los originales.
        static::updated(function ($abono) {
            PrestamoHelper::updateOnUpdate($abono);
        });

        // Al eliminar una venta, se reincorpora la cantidad vendida al inventario.
        static::deleted(function ($abono) {
            PrestamoHelper::updateOnDelete($abono);
        });
    }
}

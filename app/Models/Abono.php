<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    protected $table = 'abonos';

    protected $fillable = [
        'prestamo_id',
        'fecha_pago',
        'abono_capital',
        'intereses',
        'observaciones',
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
            static::updatePrestamoOnCreate($abono);
        });

        // En una actualización, se ajusta el inventario según la diferencia entre
        // los valores nuevos y los originales.
        static::updated(function ($abono) {
            static::updatePrestamoOnUpdate($abono);
        });

        // Al eliminar una venta, se reincorpora la cantidad vendida al inventario.
        static::deleted(function ($abono) {
            static::updatePrestamoOnDelete($abono);
        });
    }

    private static function updatePrestamoOnCreate(Abono $abono): void
    {
        $prestamo = Prestamo::where('id', $abono->prestamo_id)
            ->first();

        if ($prestamo) {
            $prestamo->saldo -= $abono->abono_capital;
            $prestamo->fecha_ultimo_pago = $abono->fecha_pago;
            $prestamo->save();
        } else {
            \Log::warning("No se encontró registro del prestamo para el prestamo ID: {$abono->id}");
        }
    }

    private static function updatePrestamoOnDelete(Abono $abono): void
    {
        $prestamo = Prestamo::where('id', $abono->prestamo_id)
            ->first();

        if ($prestamo) {
            $fechaOriginal = $prestamo->get('fecha_ultimo_pago');

            $prestamo->saldo += $abono->abono_capital;
            $prestamo->fecha_ultimo_pago = $fechaOriginal;
            $prestamo->save();
        } else {
            \Log::warning("No se encontró registro de prestamo para el abono ID: {$abono->id}");
        }
    }

    private static function updatePrestamoOnUpdate(Abono $abono): void
    {
        // Obtener los valores anteriores
        $oldCantidad = $abono->getOriginal('monto');

        // Calcular la diferencia: (valor original - valor nuevo)
        $deltaCantidad = $oldCantidad - $abono->abono_capital;

        $prestamo = Prestamo::where('id', $abono->prestamo_id)
            ->first();

        $prestamo->fecha_ultimo_pago = $abono->fecha_pago;
        if ($prestamo) {
            if ($deltaCantidad > 0) {
                $prestamo->saldo += $abono->abono_capital;
            } elseif ($deltaCantidad < 0) {
                // Si es negativa, significa que la venta aumentó,
                // se debe decrementar el inventario en la diferencia.
                $prestamo->saldo -= $abono->abono_capital;
            }
            $prestamo->save();

        } else {
            \Log::warning("No se encontró registro de prestamo para el abono ID: {$abono->id}");
        }
    }
}

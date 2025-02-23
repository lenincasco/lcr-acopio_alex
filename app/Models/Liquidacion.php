<?php

namespace App\Models;

use App\Services\AplicarCreditoService;
use Illuminate\Database\Eloquent\Model;

class Liquidacion extends Model
{
  protected $table = 'liquidaciones';

  protected $fillable = [
    'fecha_liquidacion',
    'usuario_liquida',
    'tipo_cambio',
    'total_qq_liquidados',
    'precio_liquidacion',
    'estado',
    'monto_neto',
    'prestamo_id', //NEW
    'intereses', //NEW
    'abono_capital', //NEW
    'observaciones',
  ];
  protected static function booted()
  {
    static::created(function ($abono) {
      static::updatePrestamoOnCreate($abono);
    });

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
  private static function updatePrestamoOnCreate(Liquidacion $abono): void
  {
    $detalles = $abono->detalles()->get();
    \Log::info('Cantidad de detalles: ' . $detalles->count());

    foreach ($detalles as $detalle) {
      $entrega = \App\Models\Entrega::find($detalle->entrega_id);
      if ($entrega && !$entrega->liquidada) {
        $entrega->liquidada = true;
        $entrega->save();
      }
    }
    //close update entradas liquidada = true;

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

  private static function updatePrestamoOnDelete(Liquidacion $abono): void
  {
    $detalles = $abono->detalles()->get();
    \Log::info('Cantidad de detalles: ' . $detalles->count());

    foreach ($detalles as $detalle) {
      $entrega = \App\Models\Entrega::find($detalle->entrega_id);
      if ($entrega && !$entrega->liquidada) {
        $entrega->liquidada = false;
        $entrega->save();
      }
    }
    //close update entradas liquidada = false;

    $prestamo = Prestamo::where('id', $abono->prestamo_id)
      ->first();

    if ($prestamo) {
      $prestamo->saldo += $abono->abono_capital;
      $prestamo->fecha_ultimo_pago = $prestamo->fecha_desembolso;
      $prestamo->save();
    } else {
      \Log::warning("No se encontró registro de prestamo para el abono ID: {$abono->id}");
    }
  }

  private static function updatePrestamoOnUpdate(Liquidacion $abono): void
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

  // NEW relation with User
  public function usuario()
  {
    return $this->belongsTo(User::class, 'usuario_liquida');
  }

  // Relación con el modelo Proveedor
  public function prestamo()
  {
    return $this->belongsTo(Prestamo::class);
  }

  // Relación con los detalles de la liquidación
  public function detalles()
  {
    return $this->hasMany(DetalleLiquidacion::class);
  }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\PrestamoHelper;

class Liquidacion extends Model
{
  protected $table = 'liquidaciones';

  protected $fillable = [
    'fecha_liquidacion',
    'user_id',
    'tipo_cambio',
    'total_qq_liquidados',
    'precio_liquidacion',
    'estado',
    'monto_neto',
    'prestamo_id', //NEW
    'intereses',
    'abono_capital', //NEW
    'observaciones',
  ];

  // NEW relation with User
  public function usuario()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function prestamo()
  {
    return $this->belongsTo(Prestamo::class);
  }

  // Relación con los detalles de la liquidación
  public function detalles()
  {
    return $this->hasMany(DetalleLiquidacion::class);
  }
  protected static function booted()
  {
    static::created(function ($liquidacion) {
      PrestamoHelper::updateOnCreate($liquidacion);
      static::updateEntregasOnCreate($liquidacion);
    });

    // En una actualización, se ajusta el inventario según la diferencia entre
    // los valores nuevos y los originales.
    static::updated(function ($liquidacion) {
      PrestamoHelper::updateOnUpdate($liquidacion);
    });

    // Al eliminar una venta, se reincorpora la cantidad vendida al inventario.
    static::deleting(function ($liquidacion) {
      static::updateEntregasOnDelete($liquidacion);
      PrestamoHelper::updateOnDelete($liquidacion);
    });
  }


  //*****************************************************************/
  private static function updateEntregasOnCreate(Liquidacion $liquidacion): void
  {
    $detalles = $liquidacion->detalles()->get();

    foreach ($detalles as $detalle) {
      $entrega = Entrega::find($detalle->entrega_id);
      if ($entrega) {
        $entrega->liquidada = true;
        $entrega->save();
      }
    }
  }

  private static function updateEntregasOnDelete(Liquidacion $liquidacion): void
  {
    $detalles = $liquidacion->detalles()->get();

    foreach ($detalles as $detalle) {
      $entrega = Entrega::find($detalle->entrega_id);
      if ($entrega) {
        $entrega->liquidada = false;
        $entrega->save();
      }
    }
    // Eliminar detalles manualmente después de actualizar entregas
    $liquidacion->detalles()->delete();
  }

}

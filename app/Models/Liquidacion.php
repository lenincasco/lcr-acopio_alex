<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
      DB::transaction(function () use ($liquidacion) {
        // 1. Procesar cada préstamo del repeater      
        static::UpdatePrestamos($liquidacion, "create");

        // 2. Procesar sobrante en efectivo
        if ($liquidacion->monto_sobrante > 0) {
          Caja::create([
            'monto' => $liquidacion->monto_sobrante,
            'tipo' => 'egreso',
            'concepto' => 'Sobrante liquidación #' . $liquidacion->id,
            'user_id' => $liquidacion->user_id
          ]);
        }

        // 3. Actualizar entregas y liquidaciones details
        foreach ($liquidacion->detalle_liquidacion as $detalleData) {
          $entrega = Entrega::find($detalleData['entrega_id']);
          if ($entrega) {
            if ($entrega->saldo == 0) {
              $entrega->liquidada = true; // si cancela todo el saldo
              $entrega->save();
            }
            $entrega->detallesLiquidacion()->create([
              'liquidacion_id' => $liquidacion->id,
              'entrega_id' => $entrega->id,
              'monto_entrega' => $entrega->monto_entrega,
              'qq_liquidado' => $entrega->qq_liquidado,
            ]);
          }
        }
      });
    });

    static::updated(function ($liquidacion) {
      DB::transaction(function () use ($liquidacion) {
        static::UpdatePrestamos($liquidacion, "update");

        foreach ($liquidacion->detalle_liquidacion as $detalleData) {
          $entrega = Entrega::find($detalleData['entrega_id']);
          if ($entrega) {
            if ($entrega->saldo == 0) {
              $entrega->liquidada = true;
              $entrega->save();
            }
            $entrega->detallesLiquidacion()->update([
              'liquidacion_id' => $liquidacion->id,
              'entrega_id' => $entrega->id,
              'monto_entrega' => $entrega->monto_entrega,
              'qq_liquidado' => $entrega->qq_liquidado,
            ]);
          }
        }
      });
    });

    static::deleting(function ($liquidacion) {
      DB::transaction(function () use ($liquidacion) {
        static::UpdatePrestamos($liquidacion, "delete");

        foreach ($liquidacion->detalles as $detalle) {
          $entrega = Entrega::find($detalle->entrega_id);
          if ($entrega) {
            $entrega->liquidada = false;
            $entrega->save();
          }
        }

        // Eliminar detalles manualmente después de actualizar entregas
        $liquidacion->detalles()->delete();
      });
    });
  }

  //*****************************************************************/
  private static function UpdatePrestamos(Liquidacion $liquidacion, $action): void
  {
    foreach ($liquidacion->prestamos_disponibles as $prestamoData) {
      $prestamo = Prestamo::find($prestamoData['prestamo_id']);

      if (!$prestamo) {
        return;
      }

      if ($action == "delete") {
        // Solo eliminar abonos relacionados con esta liquidación
        $prestamo->abono()->where('fecha_pago', $liquidacion->fecha_liquidacion)->delete();
      } else {
        // Calcular valores reales
        $abonoCapital = $prestamoData['abono_capital'] ?? 0;
        $intereses = $prestamoData['intereses'] ?? 0;

        // Actualizar préstamo
        $prestamo->saldo -= $abonoCapital;
        $prestamo->fecha_ultimo_pago = $liquidacion->fecha_liquidacion;
        $prestamo->save();

        // Registrar detalles de la aplicación
        $prestamo->abono()->create([
          'prestamo_id' => $prestamo->id,
          'abono_capital' => $abonoCapital,
          'intereses' => $intereses,
          'fecha_pago' => $liquidacion->fecha_liquidacion,
        ]);
      }
    }
  }

}

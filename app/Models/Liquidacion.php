<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Log;

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
  // Nueva relación con abonos
  public function abonos()
  {
    return $this->hasMany(Abono::class);
  }
  protected static function booted()
  {
    // static::created no puede actualizar el campo de cada entrada 'liquidada' a true porque esta se crea después de este proceso
    //es por ello que debe actualizarse desde el modelo DetalleLiquidacion
    static::created(function ($liquidacion) {
      DB::transaction(function () use ($liquidacion) {

        // Procesar salida de efectivo de caja
        if ($liquidacion->monto_neto > 0) {
          Caja::create([
            'monto' => $liquidacion->monto_neto,
            'tipo' => 'salida',
            'concepto' => 'liquidacion',
            'referencia' => $liquidacion->id,
            'user_id' => $liquidacion->user_id,
          ]);
        }
      });
    });

    static::deleting(function ($liquidacion) {
      DB::transaction(function () use ($liquidacion) {


        //el campo `liquidada` de cada entrada debe ser actualiza desde este proceso y no desde DetalleLiquidacion
        //de lo contrario no cambia porque no se encuentra la entrada, debido a que el detalle ya ha sido eliminado antes

        foreach ($liquidacion->detalles as $detalle) {
          $entrega = Entrega::find($detalle->entrega_id);
          if ($entrega) {
            $entrega->liquidada = false;
            $entrega->save();
          }
        }

        //revertir entrada de caja
        $caja = Caja::where('referencia', $liquidacion->id)->first();
        if ($caja) {
          $caja->delete();
        }

        // Si no hay abonos, salimos de la transacción
        if ($liquidacion->abonos->isEmpty()) {
          return;
        }

        foreach ($liquidacion->abonos as $abono) {
          // Si la fecha de pago es null, saltamos este abono
          if (!$abono->fecha_pago) {
            continue;
          }
          // Asegurarse de que el abono pertenece a esta liquidación
          if ($abono->liquidacion_id === $liquidacion->id) {
            $prestamo = $abono->prestamo;
            if ($prestamo) {
              Log::info("Saldo antes: " . $prestamo->saldo);
              Log::info("Abono al capital: " . $abono->abono_capital);
              Log::info("Saldo después: " . ($prestamo->saldo + $abono->abono_capital));
              $prestamo->saldo += $abono->abono_capital; // Revertir saldo\n                        $prestamo->save();
            }
          }
        }
      });
    });
  }

}

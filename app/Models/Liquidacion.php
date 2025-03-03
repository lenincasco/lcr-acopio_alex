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
    static::created(function ($liquidacion) {
      DB::transaction(function () use ($liquidacion) {

        $detalles = $liquidacion->detalles()->get();

        foreach ($detalles as $detalle) {
          $entrega = Entrega::find($detalle->entrega_id);
          if ($entrega) {
            $entrega->liquidada = true;
            $entrega->save();
          }
        }
      });
    });

    static::deleting(function ($liquidacion) {
      DB::transaction(function () use ($liquidacion) {
        foreach ($liquidacion->abonos as $abono) {
          if ($abono->liquidacion_id === $liquidacion->id) { // Asegurarse de que el abono pertenece a esta liquidación
            $prestamo = $abono->prestamo;

            if ($prestamo) {
              Log::info("Saldo antes: " . $prestamo->saldo);
              log::info("Abono al capital" . $abono->abono_capital);
              Log::info("Saldo después: " . ($prestamo->saldo + $abono->abono_capital));
              $prestamo->saldo += $abono->abono_capital; // Revertir saldo
              $prestamo->save();
            }
          }
        }


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

}

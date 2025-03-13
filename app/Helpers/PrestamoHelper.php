<?php
namespace App\Helpers;

use App\Models\Caja;
use App\Models\Prestamo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PrestamoHelper
{
  //********************* UTILS ***************************/
  public static function CalcularDiasInteres($prestamoId, $fechaPago)
  {
    $prestamo = Prestamo::with('proveedor')->find($prestamoId);

    if (!$prestamo) {
      return null;
    }

    $fechaUltimoPago = $prestamo->fecha_ultimo_pago ?: $prestamo->fecha_desembolso;
    $diasDiff = Carbon::parse($fechaUltimoPago)->diffInDays(Carbon::parse($fechaPago));

    $intereses = (($prestamo->monto * $prestamo->interes / 100) / 360) * $diasDiff;

    return (object) [
      'diasDiff' => round($diasDiff),
      'intereses' => round($intereses, 2),
    ];
  }

  //********************* UPDATE PRESTAMO ***************************/
  public static function updateOnCreate(Model $model): void
  {
    $prestamo = Prestamo::find($model->prestamo_id);
    Caja::create([
      'monto' => $model->abono_capital + $model->intereses,
      'tipo' => 'entrada',
      'concepto' => Config('caja.concepto.ABONO'),
      'referencia' => $model->id,
      'user_id' => auth()->id(),
    ]);

    if ($prestamo) {
      $prestamo->saldo -= $model->abono_capital;
      $prestamo->fecha_ultimo_pago = $model->fecha_liquidacion ?? $model->fecha_pago;
      $prestamo->save();
    } else {
      Log::warning("No se encontró registro del préstamo para el ID: {$model->id}");
    }
  }

  public static function updateOnDelete(Model $model): void
  {
    $prestamo = Prestamo::find($model->prestamo_id);

    if ($prestamo) {
      $prestamo->saldo += $model->abono_capital;
      $prestamo->fecha_ultimo_pago = $prestamo->fecha_desembolso ?? now();
      $prestamo->save();
    } else {
      Log::warning("No se encontró registro del préstamo para el ID: {$model->id}");
    }
  }

  public static function updateOnUpdate(Model $model): void
  {
    $prestamo = Prestamo::find($model->prestamo_id);

    $caja = Caja::where('referencia', $model->id)->first();
    if ($caja) {
      $caja->estado = $model->estado;
      $caja->save();
    }

    //los montos de los abonos no pueden ser editados
    //si el abono es anulado, se revierte el saldo
    if ($prestamo && $model->estado === "ANULADO") {
      $prestamo->saldo += $model->abono_capital;
      $prestamo->save();
    } elseif ($prestamo && $model->estado === "ACTIVO") {
      $prestamo->saldo -= $model->abono_capital;
      $prestamo->save();
    } else {
      Log::warning("No se encontró registro del préstamo para el ID: {$model->id}");
    }
  }

  /**************** SALDO FORM  **********/
  public static function saldoAfterStateHydrated(callable $set, callable $get)
  {
    $prestamoId = $get('prestamo_id');

    if (!$prestamoId) {
      $set('saldo', 0);
      return;
    }

    $prestamo = Prestamo::find($prestamoId);

    if (!$prestamo) {
      $set('saldo', 0);
      return;
    }

    $ultimoAbono = $prestamo->abonos()->latest()->first();
    $abonoCapital = $ultimoAbono ? $ultimoAbono->abono_capital : 0;

    $set('saldo', $prestamo->saldo + $abonoCapital);
  }
}

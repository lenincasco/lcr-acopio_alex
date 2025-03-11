<?php
namespace App\Helpers;

use App\Models\Abono;
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
    $oldCantidad = $model->getOriginal('monto');
    $deltaCantidad = $oldCantidad - $model->abono_capital;
    $prestamo = Prestamo::find($model->prestamo_id);

    if ($prestamo) {
      $prestamo->fecha_ultimo_pago = $model->fecha_liquidacion ?? $model->fecha_pago;
      if ($deltaCantidad > 0) {
        $prestamo->saldo += $model->abono_capital;
      } elseif ($deltaCantidad < 0) {
        $prestamo->saldo -= $model->abono_capital;
      }
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

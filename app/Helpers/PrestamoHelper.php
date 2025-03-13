<?php
namespace App\Helpers;

use App\Models\Abono;
use App\Models\Caja;
use App\Models\Prestamo;
use Carbon\Carbon;
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
  public static function updateOnCreate(Abono $abono): void
  {
    $prestamo = Prestamo::find($abono->prestamo_id);
    Caja::create([
      'monto' => $abono->abono_capital + $abono->intereses,
      'tipo' => 'entrada',
      'concepto' => Config('caja.concepto.ABONO'),
      'referencia' => $abono->id,
      'user_id' => auth()->id(),
    ]);

    if ($prestamo) {
      $prestamo->saldo -= $abono->abono_capital;
      $prestamo->fecha_ultimo_pago = $abono->fecha_liquidacion ?? $abono->fecha_pago;
      $prestamo->save();
    } else {
      Log::warning("No se encontró registro del préstamo para el ID: {$abono->id}");
    }
  }

  public static function updateOnDelete(Abono $abono): void
  {
    $prestamo = Prestamo::find($abono->prestamo_id);

    if ($prestamo) {
      $prestamo->saldo += $abono->abono_capital;
      $prestamo->fecha_ultimo_pago = $prestamo->fecha_desembolso ?? now();
      $prestamo->save();
    } else {
      Log::warning("No se encontró registro del préstamo para el ID: {$abono->id}");
    }
  }

  public static function updateOnUpdate(Abono $abono): void
  {
    $prestamo = Prestamo::find($abono->prestamo_id);

    $caja = Caja::where('referencia', $abono->id)->first();
    if ($caja) {
      $caja->estado = $abono->estado;
      $caja->save();
    }

    //los montos de los abonos no pueden ser editados
    //si el abono es anulado, se revierte el saldo
    if ($prestamo && $abono->estado === "ANULADO") {
      $prestamo->saldo += $abono->abono_capital;
      $prestamo->save();
    } elseif ($prestamo && $abono->estado === "ACTIVO") {
      $prestamo->saldo -= $abono->abono_capital;
      $prestamo->save();
    } else {
      Log::warning("No se encontró registro del préstamo para el ID: {$abono->id}");
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

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

    //Actualizar estado de la caja relacionada
    $caja = Caja::where('referencia', $abono->id)->first();
    if ($caja) {
      $caja->estado = $abono->estado;
      $caja->save();
    }

    //los montos de los abonos no pueden ser editados
    //si el abono es anulado, se revierte el saldo
    if ($prestamo && $abono->estado === "ANULADO") {
      $prestamo->saldo += $abono->abono_capital;

      // Buscar último abono ACTIVO (excluyendo el actual)
      $ultimoAbonoActivo = Abono::where('prestamo_id', $prestamo->id)
        ->where('estado', 'ACTIVO')
        ->where('id', '!=', $abono->id)
        ->latest('fecha_pago')
        ->first();
      //Actualizamos la fecha_ultimo_pago tomando la fecha_pago
      //si no se encuentran ningún abono se asigna la fecha_desembolso del préstamo
      $prestamo->fecha_ultimo_pago = $ultimoAbonoActivo
        ? $ultimoAbonoActivo->fecha_pago
        : $prestamo->fecha_desembolso;

      $prestamo->save();
    } elseif ($prestamo && $abono->estado === "ACTIVO") {
      //Nos aseguramos de tener la última fecha actualizada cuando el estado es "ACTIVO"
      //y actualizamod el saldo
      $prestamo->fecha_ultimo_pago = $abono->fecha_pago;
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

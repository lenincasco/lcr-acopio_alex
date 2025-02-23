<?php
namespace App\Helpers;

use App\Models\Prestamo;
use Carbon\Carbon;

class PrestamoHelper
{
  public static function calcularAbono($prestamoId, $monto, $fechaPago)
  {
    $prestamo = Prestamo::with('proveedor')->find($prestamoId);

    if (!$prestamo) {
      return null;
    }

    $fechaUltimoPago = $prestamo->fecha_ultimo_pago ?: $prestamo->fecha_desembolso;
    $diasDiff = Carbon::parse($fechaUltimoPago)->diffInDays(Carbon::parse($fechaPago));

    $intereses = (($prestamo->monto * $prestamo->interes / 100) / 360) * $diasDiff;
    $abonoCapital = floatval($monto) - $intereses;

    return (object) [
      'diasDiff' => round($diasDiff),
      'intereses' => round($intereses, 2),
      'abonoCapital' => round($abonoCapital, 2),
    ];
  }
}

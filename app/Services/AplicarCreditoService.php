<?php

namespace App\Services;

use App\Models\Prestamo;

class AplicarCreditoService
{
  /**
   * Aplica el monto de crédito a los préstamos del proveedor, de forma secuencial.
   *
   * @param int   $proveedorId
   * @param float $montoCreditoAplicado
   * @return float El remanente de crédito que no se pudo aplicar (si es > 0, significa que el crédito excede la suma de los saldos).
   */
  public static function aplicarCreditoEnPrestamos(int $proveedorId, float $montoCreditoAplicado): float
  {
    \Log::info('Aplicando crédito a los préstamos del proveedor');
    // Obtener préstamos del proveedor con saldo (monto_total) mayor a 0, ordenados por fecha de desembolso ascendente.
    $prestamos = Prestamo::where('proveedor_id', $proveedorId)
      ->where('monto_total', '>', 0)
      ->orderBy('fecha_desembolso', 'asc')
      ->get();
    \Log::info($prestamos);

    $remainingCredit = $montoCreditoAplicado;

    foreach ($prestamos as $prestamo) {
      if ($remainingCredit <= 0) {
        break;
      }

      $saldo = $prestamo->monto_total;

      if ($remainingCredit >= $saldo) {
        // Se aplica crédito para cubrir totalmente el saldo del préstamo.
        $prestamo->monto_total = 0;
        $remainingCredit -= $saldo;
      } else {
        // Se aplica parte del crédito y se reduce el saldo del préstamo.
        $prestamo->monto_total = $saldo - $remainingCredit;
        $remainingCredit = 0;
      }
      \Log::info('Monto aplicado a Préstamo: ' . $prestamo);
      $prestamo->save();
    }

    return $remainingCredit;
  }
}

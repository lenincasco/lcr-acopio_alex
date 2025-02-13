<?php

namespace App\Observers;

use App\Models\Prestamo;
use App\Models\Proveedor;

class PrestamoObserver
{
  /**
   * Se ejecuta después de crear un préstamo.
   */
  public function created(Prestamo $prestamo)
  {
    $this->actualizarCreditoDisponible($prestamo->proveedor);
  }

  /**
   * Se ejecuta después de actualizar un préstamo.
   */
  public function updated(Prestamo $prestamo)
  {
    $this->actualizarCreditoDisponible($prestamo->proveedor);
  }

  /**
   * Se ejecuta después de eliminar un préstamo.
   */
  public function deleted(Prestamo $prestamo)
  {
    $this->actualizarCreditoDisponible($prestamo->proveedor);
  }

  /**
   * El crédito disponible se calcula como la suma de los
   * montos totales de todos los préstamos activos del proveedor.
   *
   * @param Proveedor $proveedor
   */
  protected function actualizarCreditoDisponible(Proveedor $proveedor)
  {
    if (!$proveedor) {
      return;
    }

    // sumar el monto_total de todos los préstamos activos del proveedor.
    $nuevoCreditoDisponible = Prestamo::where('proveedor_id', $proveedor->id)
      ->sum('monto_total');

    $proveedor->credito_disponible = $nuevoCreditoDisponible;
    $proveedor->save();
  }
}

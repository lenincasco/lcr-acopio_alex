<?php
namespace App\Helpers;

use App\Models\Entrega;
use App\Models\Prestamo;
use Filament\Notifications\Notification;
use Log;

class LiquidacionHelper
{
  /************************************/
  public static function afterHydratedProveedorId(callable $set, $livewire): void
  {
    if (filled($livewire->record)) {
      // Cargar datos existentes del registro
      $liquidacion = $livewire->record;
      $prestamosDisponibles = $liquidacion->abonos->map(function ($abono) {
        return [
          'prestamo_id' => $abono->prestamo_id,
          'saldo' => $abono->prestamo->saldo,
          'dias_diff' => $abono->dias_diff,
          'abono_capital' => $abono->abono_capital,
          'intereses' => $abono->intereses,
          'qq_abonados' => $abono->qq_abonados,
        ];
      })->toArray();
      Log::info('datos: ' . json_encode($prestamosDisponibles));
      // Llenar repeaters con datos guardados
      $set('prestamos_disponibles', $prestamosDisponibles);

      $set('detalle_liquidacion', $liquidacion->detalles->map(function ($detalle) {
        return [
          'entrega_id' => $detalle->entrega_id,
          'cantidad_sacos' => $detalle->entrega->cantidad_sacos,
          'qq_liquidable' => $detalle->entrega->qq_liquidable,
          'qq_liquidado' => $detalle->qq_liquidado,
        ];
      })->toArray());
    }
  }

  /************************************/
  public static function afterUpdatedProveedorId(callable $set, string $state): void
  {
    // Cargar préstamos activos del proveedor
    $prestamos = Prestamo::where('proveedor_id', $state)
      ->where('saldo', '>', 0)
      ->orderBy('fecha_desembolso')
      ->get();

    //$set('prestamos_disponibles', $prestamos->toArray());
    $prestamoRepeater = $prestamos->map(function ($prestamo) {
      return [
        'prestamo_id' => $prestamo->id,
        'saldo' => $prestamo->saldo,
      ];
    })->toArray();
    $set('prestamos_disponibles', $prestamoRepeater);

    //FILL ENTREGAS REPEATER
    $entregas = Entrega::where('proveedor_id', $state)
      ->where('liquidada', false)
      ->where('tipo_entrega', 'ENTREGA')
      ->get();
    $repeaterData = $entregas->map(function ($entrega) {
      return [
        'entrega_id' => $entrega->id,
        'cantidad_sacos' => $entrega->cantidad_sacos,
        'qq_liquidable' => $entrega->quintalaje_liquidable,
        'qq_liquidado' => 0,
      ];
    })->toArray();
    if (count($repeaterData) === 0) {
      Notification::make()
        ->title("Sin entregas")
        ->body('El proveedor no tiene entregas disponibles para liquidar')
        ->warning()
        ->send();
    }
    $set('detalle_liquidacion', $repeaterData);
    $qq_pagar = collect($repeaterData)->sum(fn($row) => (float) ($row['qq_liquidable'] ?? 0));
    $set('qq_pagar', $qq_pagar);
  }


  /**********************************/
  public static function recalcularTotales(callable $set, callable $get): void
  {
    $precioLiquidacion = floatVal($get('precio_liquidacion'));
    $totalQQAbonados = floatVal($get('total_qq_abonados'));
    $fechaLiquidacion = $get('fecha_liquidacion');

    if (!$precioLiquidacion) {
      return;
    }

    $detalle = $get('detalle_liquidacion') ?? [];

    $TotalEntregasQQ = collect($detalle)->sum(fn($row) => (float) ($row['qq_liquidable'] ?? 0));
    if ($TotalEntregasQQ === 0) {
      Notification::make()
        ->title("Sin entregas")
        ->body('El proveedor no tiene entregas disponibles para liquidar')
        ->warning()
        ->send();
      $set('precio_liquidacion', '');
      return;
    }
    if ($TotalEntregasQQ < $totalQQAbonados) {
      Notification::make()
        ->title("Quintalaje disponible: $TotalEntregasQQ QQ")
        ->body('La cantidad de quintales que deseas liquidar excede al disponible.')
        ->warning()
        ->send();
      $set('total_qq_liquidados', $TotalEntregasQQ);
      return;
    }
    $set('qq_pagar', $TotalEntregasQQ - $totalQQAbonados);

    $montoQQLiquida = $totalQQAbonados * $precioLiquidacion;

    $prestamosDisponibles = $get('prestamos_disponibles') ?? [];
    $sobranteMontoQQLiquida = $montoQQLiquida;
    $totalInreses = 0;
    $totalAbonoCapital = 0;

    /********* Prestamos *********/
    foreach ($prestamosDisponibles as $index => $prestamo) {
      $saldoActual = floatVal($prestamo['saldo']) ?? 0;

      if ($saldoActual > 0 && $sobranteMontoQQLiquida > 0 && $totalQQAbonados > 0) {
        // Obtener los intereses que deben ser descontados
        $datosAbono = PrestamoHelper::CalcularDiasInteres($prestamo['prestamo_id'], $fechaLiquidacion);
        $intereses = floatval($datosAbono->intereses);
        $totalInreses += $intereses;//actualiza totales
        // Primero se descuenta el interés del sobrante
        if ($sobranteMontoQQLiquida >= $intereses) {
          $sobranteMontoQQLiquida -= $intereses;
        } else {
          // Si el sobrante no cubre los intereses, no hay abono a capital
          $set("prestamos_disponibles.{$index}.intereses", $sobranteMontoQQLiquida);
          $set("prestamos_disponibles.{$index}.abono_capital", 0);
          $sobranteMontoQQLiquida = 0;
          break;
        }

        // Lo que queda del sobrante se usa para el abono a capital
        $montoAplicar = min($saldoActual, $sobranteMontoQQLiquida);
        $nuevoSaldo = $saldoActual - $montoAplicar;
        $abonoCapital = $montoAplicar;
        $totalAbonoCapital += $abonoCapital;//actualiza totales

        $sobranteMontoQQLiquida -= $abonoCapital;
        $qqAbonados = round(($abonoCapital + $intereses) / $precioLiquidacion, 2);
        // Guardar los valores en el formulario
        $set("prestamos_disponibles.{$index}.nuevo_saldo", round($nuevoSaldo, 2));
        $set("prestamos_disponibles.{$index}.abono_capital", round($abonoCapital, 2));
        $set("prestamos_disponibles.{$index}.intereses", round($intereses, 2));
        $set("prestamos_disponibles.{$index}.dias_diff", $datosAbono->diasDiff);
        $set("prestamos_disponibles.{$index}.qq_abonados", $qqAbonados);
        $set("prestamos_disponibles.{$index}.fecha_pago", $fechaLiquidacion);

      } else {
        //si el proveedor no tiene préstamos
        $set("prestamos_disponibles.{$index}.nuevo_saldo", round($saldoActual, 2));
        $set("prestamos_disponibles.{$index}.abono_capital", 0);
        $set("prestamos_disponibles.{$index}.intereses", 0);
      }

    }

    // ******* asignar a cada DETALLE su monto total y cantidad de qq_liquidados *****	
    foreach ($detalle as $index => $row) {
      $qq_liquidados = floatVal($row['qq_liquidable']) - $totalQQAbonados;
      if ($qq_liquidados < 0) {
        $qq_liquidados = floatVal($row['qq_liquidable']);
      } else {
        $qq_liquidados = $totalQQAbonados;
      }
      $totalQQAbonados -= $qq_liquidados;
      $set("detalle_liquidacion.{$index}.monto_entrega", $precioLiquidacion * $qq_liquidados);
      $set("detalle_liquidacion.{$index}.qq_liquidado", $qq_liquidados);
    }

    //TOTALES
    $set('total_qq_liquidados', $TotalEntregasQQ);
    $set('total_intereses', $totalInreses);
    $set('total_abono_capital', $totalAbonoCapital);
    $set('monto_neto', $TotalEntregasQQ * $precioLiquidacion);
    $efectivoCliente = ($TotalEntregasQQ * $precioLiquidacion) - ($totalAbonoCapital + $totalInreses);
    $set('efectivo_cliente', $efectivoCliente);

    //Establecer la max cant. de QQ que el proveedor debe abonar
    $maxAbonoQQ = ($totalAbonoCapital + $totalInreses) / $precioLiquidacion;
    $set('total_qq_abonados', round($maxAbonoQQ, 3));
  }
}
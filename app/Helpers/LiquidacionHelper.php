<?php
namespace App\Helpers;

use App\Models\Entrega;
use App\Models\Liquidacion;
use App\Models\Prestamo;
use Filament\Notifications\Notification;
use Log;

class LiquidacionHelper
{
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
  public static function recalcularTotales(callable $set, callable $get, $livewire): void
  {
    $precioLiquidacion = floatVal($get('precio_liquidacion'));
    $totalQQAbonados = floatVal($get('total_qq_abonados'));
    $fechaLiquidacion = $get('fecha_liquidacion');

    if (!$precioLiquidacion)
      return;//evita cálculos innecesarios

    //comprobar si el usuario se encuentra en modo EDIT o CREATE
    //la limitacion del quintalaje_disponible debe variar
    $isEdit = filled($livewire->record);
    $contexto = $isEdit ? 'edit' : 'create';
    Log::info('context: ' . $contexto);

    $repeaterDetalles = $get('detalle_liquidacion') ?? [];

    //Si el usuario está en modo CREATE y $repeaterDetalles es un array vacío, quiere decir que no ha pasado el filtro del formulario, 
    //y el proveedor tiene todas tus entradas como liquidada = true. Entonces no se puede crear una liquidación para este proveedor
    $TotalEntregasQQ = collect($repeaterDetalles)->sum(fn($row) => (float) ($row['qq_liquidable'] ?? 0));

    // Nuevo: Obtener quintales originales en edición
    $quintalesOriginales = filled($get('id'))
      ? Liquidacion::find($get('id'))->total_qq_liquidados
      : $TotalEntregasQQ;

    // Validación ajustada para edición
    if ($isEdit)
      $TotalEntregasQQ = $quintalesOriginales;
    if ($totalQQAbonados > $TotalEntregasQQ) {
      Notification::make()
        ->title("Límite excedido")
        ->body("No puedes asignar más de $TotalEntregasQQ QQ (liquidación original)")
        ->warning()
        ->send();
      $set('total_qq_abonados', $TotalEntregasQQ);
      return;
    } elseif ($TotalEntregasQQ < $totalQQAbonados && $contexto === 'create') {
      Notification::make()
        ->title("Quintalaje disponible: $TotalEntregasQQ QQ")
        ->body('La cantidad excede al disponible.')
        ->warning()
        ->send();
      $set('total_qq_abonados', $TotalEntregasQQ);
      return;
    }

    $set('qq_pagar', $TotalEntregasQQ - $totalQQAbonados);//Quintalaje a pagar al proveedor

    //Efectivo para el préstamo; de este monto se toma primero los intereses a pagar y el sobrante como abono al capital
    $montoQQAbona = $totalQQAbonados * $precioLiquidacion;
    Log::info("montoQQLiquida: $montoQQAbona");

    //init values
    $repeaterPrestamos = $get('prestamos_disponibles') ?? [];
    $sobranteMontoQQAbona = $montoQQAbona;
    $totalIntereses = 0;
    $totalAbonoCapital = 0;

    /********* Prestamos *********/
    $totalSaldosPrestamos = 0;
    foreach ($repeaterPrestamos as $index => $prestamo) {
      $totalSaldosPrestamos += floatVal($prestamo['saldo']);

      $saldoActual = floatVal($prestamo['saldo']) ?? 0;
      if ($saldoActual > 0 && $sobranteMontoQQAbona > 0 && $totalQQAbonados > 0) {
        // Obtener los intereses que deben ser descontados
        $datosAbono = PrestamoHelper::CalcularDiasInteres($prestamo['prestamo_id'], $fechaLiquidacion);
        $intereses = floatval($datosAbono->intereses);
        $totalIntereses += $intereses;//actualiza total de intereses de todos los préstamos

        // Primero se descuenta el interés del sobrante
        if ($sobranteMontoQQAbona >= $intereses) {
          $sobranteMontoQQAbona -= $intereses;
        } else {
          // Si el sobrante no cubre los intereses, no habrá abono al capital
          $set("prestamos_disponibles.{$index}.intereses", $sobranteMontoQQAbona);
          $set("prestamos_disponibles.{$index}.abono_capital", 0);
          $sobranteMontoQQAbona = 0;
          break;
        }

        // Lo que queda del sobrante se usa para el abono a capital
        //El uso de min aquí garantiza que el monto que se aplicará como abono al capital ($montoAplicar) 
        //nunca exceda ni el saldo actual del préstamo ni el monto disponible sobrante.
        $montoAplicar = min($saldoActual, $sobranteMontoQQAbona);
        Log::info("montoAplicar: $montoAplicar");
        $nuevoSaldo = $saldoActual - $montoAplicar;
        $abonoCapital = $montoAplicar;
        $totalAbonoCapital += $abonoCapital;//actualiza totales

        $sobranteMontoQQAbona -= $abonoCapital;
        $qqAbonados = round(($abonoCapital + $intereses) / $precioLiquidacion, 2);
        // Guardar los valores en el formulario
        $set("prestamos_disponibles.{$index}.nuevo_saldo", round($nuevoSaldo, 2));
        $set("prestamos_disponibles.{$index}.abono_capital", round($abonoCapital, 2));
        $set("prestamos_disponibles.{$index}.intereses", round($intereses, 2));
        Log::info('dias diff: ' . $datosAbono->diasDiff);
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
    foreach ($repeaterDetalles as $index => $row) {
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
    $set('total_intereses', $totalIntereses);
    $set('total_abono_capital', $totalAbonoCapital);
    $set('monto_neto', $TotalEntregasQQ * $precioLiquidacion);
    $efectivoCliente = ($TotalEntregasQQ * $precioLiquidacion) - ($totalAbonoCapital + $totalIntereses);
    $set('efectivo_cliente', $efectivoCliente);

    $set('total_saldos_prestamos', $totalSaldosPrestamos);

    //Establecer la max cant. de QQ que el proveedor debe abonar
    $maxAbonoQQ = ($totalAbonoCapital + $totalIntereses) / $precioLiquidacion;
    $set('total_qq_abonados', round($maxAbonoQQ, 3));
  }
}
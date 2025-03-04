<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiquidacionResource\Pages;
use App\Models\Entrega;
use App\Models\Liquidacion;
use App\Models\Prestamo;
use App\Models\Proveedor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use App\Helpers\PrestamoHelper;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;

use Filament\Actions;
use Log;
use function PHPUnit\Framework\isEmpty;

class LiquidacionResource extends Resource
{
	protected static ?string $model = Liquidacion::class;
	protected static ?string $navigationGroup = 'Finanzas';

	protected static ?string $navigationIcon = 'heroicon-o-book-open';

	public static function form(Form $form): Form
	{
		return $form
			->schema([

				Section::make('Datos Generales de Liquidación') // Encabezado de la sección
					->columns(12) // 2 columnas para organizar los campos
					->columnSpan(12)
					->schema([

						Forms\Components\Select::make('proveedor_id')
							->label('Proveedor/Deudor')
							->columnSpan(3)
							->relationship('prestamo.proveedor', 'nombrecompleto')
							->required()
							->searchable()
							->reactive()
							->afterStateUpdated(function (callable $set, $state) {
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
							})
							->required()
							->reactive(),

						Forms\Components\DatePicker::make('fecha_liquidacion')
							->label('Fecha de Liquidación')
							->columnSpan(2)
							->required()
							->reactive()
							->debounce(500)
							->afterStateUpdated(function (callable $set, callable $get) {
								self::recalcularTotales($set, $get);
							}),

						Forms\Components\TextInput::make('precio_liquidacion')
							->label('Precio de Liquidación por QQ (C$)')
							->required()
							->numeric()
							->placeholder('En córdobas')
							->columnSpan(3)
							->reactive()
							->debounce(750)
							->afterStateUpdated(function (callable $set, callable $get) {
								self::recalcularTotales($set, $get);
							}),

						Forms\Components\TextInput::make('qq_abonados')
							->label('Cant. QQ que Abona')
							->default(0)
							->placeholder('Por ejemplo: 20')
							->columnSpan(3)
							->reactive()
							->numeric()
							->debounce(750)
							->afterStateUpdated(function ($set, $get) {
								self::recalcularTotales($set, $get);
							})
							->hidden(function (callable $get) {
								// Obtener el ID del proveedor seleccionado
								$proveedorId = $get('proveedor_id');

								// Verificar si existe al menos un préstamo con saldo > 0
								$tienePrestamosPorLiquidar = $proveedorId && Prestamo::where('proveedor_id', $proveedorId)
									->where('saldo', '>', 0)
									->exists();
								if ($tienePrestamosPorLiquidar) {
									return false;//hidden = false
								}
							}),

						Forms\Components\TextInput::make('tipo_cambio')
							->label('Tipo de Cambio')
							->required()
							->placeholder('Por ejemplo: 36.45')
							->columnSpan(3)
							->numeric(),

						Forms\Components\Textarea::make('observaciones')
							->label('Observaciones')
							->columnSpan('full') // Ocupa toda la fila
							->columnSpan(6)
							->nullable(),
						Hidden::make('user_id')
							->default(auth()->id())
							->dehydrated(true),
					]), // Fin de la sección Datos Generales

				/******************* TOTALES ****************/
				Section::make('Totales')
					->columns(12)
					->columnSpan(12)
					->schema([
						Forms\Components\TextInput::make('total_qq_liquidados')
							->label('QQ Entregas')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),
						Forms\Components\TextInput::make('total_intereses')
							->label('Intereses  (C$)')
							->columnSpan(2)
							->disabled(),
						Forms\Components\TextInput::make('total_abono_capital')
							->label('Abono al capital')
							->columnSpan(2)
							->disabled(),
						Forms\Components\TextInput::make('monto_neto')
							->label('Monto Neto')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),
						Forms\Components\TextInput::make('qq_pagar')
							->label('QQ a pagar')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),
						Forms\Components\TextInput::make('efectivo_cliente')
							->label('Efectivo para el cliente')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),

					]), // Fin de la sección Totales

				/********************************** */

				Forms\Components\Repeater::make('prestamos_disponibles')
					->relationship('abonos')
					->label('PRÉSTAMOS PENDIENTES POR LIQUIDAR:')
					->columns(12)
					->columnSpan(12)
					->schema([
						Forms\Components\Select::make('prestamo_id')
							->label('Id')
							->options(function (callable $get) {
								$prestamoId = $get('prestamo_id');
								if ($prestamoId) {
									$prestamo = Prestamo::find($prestamoId);
									return $prestamo ? [$prestamo->id => $prestamo->id ?? 'Sin préstamo'] : [];
								}
								return [];
							})
							->columnSpan(2)
							->disabled()
							->dehydrated(true),

						Forms\Components\TextInput::make('saldo')
							->label('Saldo Anterior')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),
						Forms\Components\TextInput::make('nuevo_saldo')
							->label('Nuevo saldo')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),
						Forms\Components\TextInput::make('dias_diff')
							->label('Dias')
							->columnSpan(2)
							->disabled(),
						Forms\Components\TextInput::make('intereses')
							->label('Intereses (C$)')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),
						Forms\Components\TextInput::make('abono_capital')
							->label('Abono al capital')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),
						Forms\Components\DatePicker::make('fecha_pago')
							->columnSpan(2)
							->default(function ($get) {
								return $get('../../fecha_liquidacion'); // Ruta al campo padre
							})
							->disabled()
							->dehydrated(true),
					])
					->disableItemCreation()
					->disableItemDeletion()
					->hidden(fn($get) => empty($get('qq_abonados'))),

				Forms\Components\Repeater::make('detalle_liquidacion')
					->relationship('detalles')
					->label('ENTREGAS:')
					->columns(12)
					->columnSpan(12)
					->visible(function (callable $get) {
						// Sección visible SOLO si hay detalles de liquidación en el repeater
						$detalleLiquidacion = $get('detalle_liquidacion') ?? [];
						return !empty($detalleLiquidacion); // Visible si el array de detalles NO está vacío
					})
					->schema([
						Forms\Components\Select::make('entrega_id')
							->label('Entrega')
							->columnSpan(3)
							->options(function (callable $get) {
								$prestamoId = $get('entrega_id');
								if ($prestamoId) {
									$entrega = Entrega::find($prestamoId);
									return $entrega ? [$entrega->id => $entrega->id ?? 'Sin código'] : [];
								}
								return [];
							})
							->required()
							->disabled()
							->dehydrated(true),

						Forms\Components\TextInput::make('cantidad_sacos')
							->label('Cantidad de Sacos')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),

						Forms\Components\TextInput::make('qq_liquidable')
							->label('Quintalaje liquidable')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),
						Forms\Components\TextInput::make('qq_liquidado')
							->label('QQ Abona')
							->columnSpan(2)
							->disabled()
							->dehydrated(true),

						Forms\Components\TextInput::make('monto_entrega')
							->label('Monto de la Entrega  (C$)')
							->columnSpan(3)
							->reactive()
							->disabled()
							->dehydrated(true)
							->required()
							->afterStateUpdated(function (callable $set, $state, callable $get) {
								self::recalcularTotales($set, $get);
							}),
					])
					->minItems(1)
					->disableItemCreation()
					->disableItemDeletion()
					->afterStateUpdated(function (callable $get, callable $set, $state) {
						self::recalcularTotales($set, $get);
					}),

				Section::make('No hay resultados')
					->visible(function (callable $get) {
						$detalleLiquidacion = $get('detalle_liquidacion') ?? [];
						return empty($detalleLiquidacion);
					}),
			]);
	}

	// Función reutilizable para recalcular totales (monto bruto, neto, total qq)
	private static function recalcularTotales(callable $set, callable $get): void
	{
		$precioLiquidacion = floatVal($get('precio_liquidacion'));
		$qqAbonados = floatVal($get('qq_abonados'));
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
		if ($TotalEntregasQQ < $qqAbonados) {
			Notification::make()
				->title("Quintalaje disponible: $TotalEntregasQQ QQ")
				->body('La cantidad de quintales que deseas liquidar excede al disponible.')
				->warning()
				->send();
			$set('total_qq_liquidados', $TotalEntregasQQ);
			return;
		}
		$set('qq_pagar', $TotalEntregasQQ - $qqAbonados);

		$montoQQLiquida = $qqAbonados * $precioLiquidacion;

		$prestamosDisponibles = $get('prestamos_disponibles') ?? [];
		$sobranteMontoQQLiquida = $montoQQLiquida;
		$totalInreses = 0;
		$totalAbonoCapital = 0;

		/********* Prestamos *********/
		foreach ($prestamosDisponibles as $index => $prestamo) {
			$saldoActual = floatVal($prestamo['saldo']) ?? 0;

			if ($saldoActual > 0 && $sobranteMontoQQLiquida > 0 && $qqAbonados > 0) {
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

				// Guardar los valores en el formulario
				$set("prestamos_disponibles.{$index}.nuevo_saldo", $nuevoSaldo);
				$set("prestamos_disponibles.{$index}.abono_capital", $abonoCapital);
				$set("prestamos_disponibles.{$index}.intereses", $intereses);
				$set("prestamos_disponibles.{$index}.dias_diff", $datosAbono->diasDiff);
				$set("prestamos_disponibles.{$index}.fecha_pago", $fechaLiquidacion);
			} else {
				$set("prestamos_disponibles.{$index}.nuevo_saldo", $saldoActual);
				$set("prestamos_disponibles.{$index}.abono_capital", 0);
				$set("prestamos_disponibles.{$index}.intereses", 0);
			}

		}

		// ******* Recalcular montos de entrega al cambiar precio_liquidacion *****	
		foreach ($detalle as $index => $row) {
			$qq_liquidados = floatVal($row['qq_liquidable']) - $qqAbonados;
			if ($qq_liquidados < 0) {
				$qq_liquidados = floatVal($row['qq_liquidable']);
			} else {
				$qq_liquidados = $qqAbonados;
			}
			$qqAbonados -= $qq_liquidados;
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
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('detalles.entrega.proveedor.nombrecompleto')
					->label('Proveedor')
					->sortable()
					->searchable(),
				Tables\Columns\TextColumn::make('total_qq_liquidados')
					->label('Total QQ Liquidados')
					->sortable(),
				Tables\Columns\TextColumn::make('monto_neto')
					->label('Monto Neto')
					->money('NIO', locale: 'es_NI')
					->sortable()
					->formatStateUsing(fn($state) => 'C$ ' . number_format($state, 2)),
				Tables\Columns\TextColumn::make('estado')
					->sortable(),
				Tables\Columns\TextColumn::make('fecha_liquidacion')
					->label('Fecha de Liquidación')
					->dateTime()
					->sortable(),
				Tables\Columns\TextColumn::make('user.name') // Muestra el nombre del usuario que liquida
					->label('Usuario Liquida')
					->sortable(),
			])
			->filters([
				//
			])
			->actions([
				Tables\Actions\EditAction::make(),
			])
			->bulkActions([
				Tables\Actions\BulkActionGroup::make([
					Tables\Actions\DeleteBulkAction::make(),
				]),
			]);
	}

	public static function getRelations(): array
	{
		return [
			//
		];
	}

	public static function getPages(): array
	{
		return [
			'index' => Pages\ListLiquidacions::route('/'),
			'create' => Pages\CreateLiquidacion::route('/create'),
			'edit' => Pages\EditLiquidacion::route('/{record}/edit'),
		];
	}

	public static function getActions(): array
	{
		return [
			Actions\CreateAction::make()
				->disable(function (callable $get) {
					$detalleLiquidacion = $get('detalle_liquidacion') ?? [];
					return empty($detalleLiquidacion);
				}),
		];
	}
}
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiquidacionResource\Pages;
use App\Models\Entrega;
use App\Models\Liquidacion;
use App\Models\Prestamo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use App\Helpers\PrestamoHelper;
use Filament\Forms\Components\Hidden;

use Filament\Actions;

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
					->schema([

						Forms\Components\Select::make('prestamo_id')
							->label('Préstamo a Liquidar')
							->columnSpan(4)
							->options(function () {
								return Prestamo::with('proveedor')
									->where('saldo', '>', 0)
									->get()
									->mapWithKeys(function ($prestamo) {
										$label = "Préstamo #{$prestamo->id} - {$prestamo->proveedor->nombrecompleto}";
										return [$prestamo->id => $label];
									});
							})
							->searchable()
							->placeholder('Selecciona un proveedor/deudor')
							->noSearchResultsMessage('No hay préstamos para liquidar')
							->searchPrompt('Ingresa el nombre del deudor')
							->required()
							->reactive()
							->afterStateUpdated(function (callable $set, $state) {
								// Al seleccionar proveedor, actualiza el repeater y muestra el crédito disponible
								$entregas = Entrega::where('proveedor_id', $state)
									->where('liquidada', false)
									->where('tipo_entrega', 'ENTREGA')
									->get();
								$repeaterData = $entregas->map(function ($entrega) {
									return [
										'entrega_id' => $entrega->id,
										'cantidad_sacos' => $entrega->cantidad_sacos,
										'qq_liquidado' => $entrega->quintalaje_liquidable,
									];
								})->toArray();
								$set('detalle_liquidacion', $repeaterData);
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
							->debounce(500)
							->afterStateUpdated(function (callable $set, $state, callable $get) {
								// Recalcular montos de entrega al cambiar precio_liquidacion
								$detalle = $get('detalle_liquidacion') ?? [];
								foreach ($detalle as $index => $row) {
									$quintalaje = $row['qq_liquidado'] ?? 0;
									$set("detalle_liquidacion.{$index}.monto_entrega", $state * $quintalaje);
								}
								// Recalcular los totales (incluyendo monto bruto y neto)
								self::recalcularTotales($set, $get);
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

				Section::make('Totales') // Nueva sección para montos y créditos
					->visible(function (callable $get) {
						$detalleLiquidacion = $get('detalle_liquidacion') ?? [];
						return !empty($detalleLiquidacion);
					})
					->columns(5)
					->schema([
						Forms\Components\TextInput::make('dias_diff')
							->label('Dias')
							->disabled(),
						Forms\Components\TextInput::make('total_qq_liquidados')
							->label('Total QQ Liquidados')
							->disabled()
							->dehydrated(true),
						Forms\Components\TextInput::make('intereses')
							->label('Intereses  (C$)')
							->disabled()
							->dehydrated(true),
						Forms\Components\TextInput::make('abono_capital')
							->label('Abono al capital  (C$)')
							->disabled()
							->dehydrated(true),

						Forms\Components\TextInput::make('monto_neto')
							->label('Monto Neto  (C$)')
							->disabled()
							->dehydrated(true),

					]), // Fin de la sección Totales


				Forms\Components\Repeater::make('detalle_liquidacion')
					->relationship('detalles')
					->label('ENTREGAS:')
					->columnSpan('full')
					->visible(function (callable $get) {
						// Sección visible SOLO si hay detalles de liquidación en el repeater
						$detalleLiquidacion = $get('detalle_liquidacion') ?? [];
						return !empty($detalleLiquidacion); // Visible si el array de detalles NO está vacío
					})
					->schema([
						Section::make('')
							->columns(4)
							->schema([
								Forms\Components\Select::make('entrega_id')
									->label('Entrega')
									->options(function (callable $get) {
										$entregaId = $get('entrega_id');
										if ($entregaId) {
											$entrega = Entrega::find($entregaId);
											return $entrega ? [$entrega->id => $entrega->id ?? 'Sin código'] : [];
										}
										return [];
									})
									->required()
									->disabled()
									->dehydrated(true),

								Forms\Components\TextInput::make('cantidad_sacos')
									->label('Cantidad de Sacos')
									->disabled()
									->dehydrated(true),

								Forms\Components\TextInput::make('qq_liquidado')
									->label('Quintales liquidados')
									->disabled()
									->dehydrated(true),

								Forms\Components\TextInput::make('monto_entrega')
									->label('Monto de la Entrega  (C$)')
									->reactive()
									->disabled()
									->dehydrated(true)
									->required()
									->afterStateUpdated(function (callable $set, $state, callable $get) {
										self::recalcularTotales($set, $get);
									}),
							]),//close Section
					])
					->minItems(1)
					->disableItemCreation()
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
		$precioLiquidacion = $get('precio_liquidacion');
		if (!$precioLiquidacion) {
			return;
		}

		$detalle = $get('detalle_liquidacion') ?? [];
		$montoNeto = max(0, collect($detalle)->sum(fn($row) => (float) ($row['monto_entrega'] ?? 0)));//el metodo max se asegura que no se genenen valores negativos
		$total_qq = collect($detalle)->sum(fn($row) => (float) ($row['qq_liquidado'] ?? 0));

		$datosAbono = PrestamoHelper::calcularAbono($get('prestamo_id'), $montoNeto, $get('fecha_liquidacion'));
		$set('intereses', $datosAbono->intereses);
		$set('abono_capital', $datosAbono->abonoCapital);
		$set('dias_diff', $datosAbono->diasDiff);
		$set('total_qq_liquidados', $total_qq);
		$set('monto_neto', $montoNeto);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('prestamo.proveedor.nombrecompleto') // Muestra el nombre del proveedor
					->label('Proveedor')
					->sortable()
					->searchable(),
				Tables\Columns\TextColumn::make('total_qq_liquidados')
					->label('Total QQ Liquidados')
					->sortable(),
				Tables\Columns\TextColumn::make('monto_neto')
					->label('Monto Neto')
					->sortable()
					->formatStateUsing(fn($state) => 'C$ ' . number_format($state, 2)),
				Tables\Columns\TextColumn::make('intereses')
					->label('Intereses')
					->sortable()
					->formatStateUsing(fn($state) => 'C$ ' . number_format($state, 2)),
				Tables\Columns\TextColumn::make('abono_capital')
					->label('Abono al capital')
					->sortable()
					->formatStateUsing(fn($state) => 'C$ ' . number_format($state, 2)),
				Tables\Columns\TextColumn::make('estado')
					->sortable(),
				Tables\Columns\TextColumn::make('fecha_liquidacion')
					->label('Fecha de Liquidación')
					->dateTime()
					->sortable(),
				Tables\Columns\TextColumn::make('usuario.name') // Muestra el nombre del usuario que liquida
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
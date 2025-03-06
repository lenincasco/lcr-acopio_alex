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
use App\Helpers\LiquidacionHelper;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;

use Filament\Actions;
use Log;
use function PHPUnit\Framework\isEmpty;

class LiquidacionResource extends Resource
{
	protected static ?string $model = Liquidacion::class;
	protected static ?string $navigationGroup = 'Finanzas';
	protected static ?int $navigationSort = 3;

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
							->disabled(fn($livewire): bool => filled($livewire->record)) // Deshabilitado solo en edición
							// Verificar si es modo edición
							->afterStateHydrated(function (callable $set, $livewire) { // Usar $livewire					
								LiquidacionHelper::afterHydratedProveedorId($set, $livewire);
							})
							->afterStateUpdated(function (callable $set, string $state) {
								LiquidacionHelper::afterUpdatedProveedorId($set, $state);
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
								LiquidacionHelper::recalcularTotales($set, $get);
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
								LiquidacionHelper::recalcularTotales($set, $get);
							}),

						Forms\Components\TextInput::make('total_qq_abonados')
							->label('Cant. QQ que Abona')
							->default(0)
							->placeholder('Por ejemplo: 20')
							->columnSpan(3)
							->reactive()
							->numeric()
							->debounce(750)
							->afterStateUpdated(function (callable $set, callable $get) {
								LiquidacionHelper::recalcularTotales($set, $get);
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
						Forms\Components\Select::make('activa')
							->columnSpan(2)
							->default(true)
							->options([
								true => 'ACTIVA',
								false => 'ANULADA',
							])
							->hidden(fn($livewire): bool => empty($livewire->record)),

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

				/**************** ABONOS ****************** */

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
						Forms\Components\TextInput::make('qq_abonados')
							->label('QQ Abonados')
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
					->disableItemDeletion(),

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
							->label('QQ liquidados')
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
								LiquidacionHelper::recalcularTotales($set, $get);
							}),
					])
					->minItems(1)
					->disableItemCreation()
					->disableItemDeletion()
					->afterStateUpdated(function (callable $get, callable $set, $state) {
						LiquidacionHelper::recalcularTotales($set, $get);
					}),

				Section::make('No hay resultados')
					->visible(function (callable $get) {
						$detalleLiquidacion = $get('detalle_liquidacion') ?? [];
						return empty($detalleLiquidacion);
					}),
			]);
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
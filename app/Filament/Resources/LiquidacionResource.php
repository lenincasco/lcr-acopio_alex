<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiquidacionResource\Pages;
use App\Models\Entrega;
use App\Models\Liquidacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;

use Filament\Actions;

class LiquidacionResource extends Resource
{
    protected static ?string $model = Liquidacion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos Generales de Liquidación') // Encabezado de la sección
                    ->columns(2) // 2 columnas para organizar los campos
                    ->schema([
                        Forms\Components\Select::make('proveedor_id')
                            ->label('Proveedor')
                            ->relationship('proveedor', 'nombrecompleto')
                            ->searchable()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Al seleccionar proveedor, actualiza el repeater y muestra el crédito disponible
                                $entregas = Entrega::where('proveedor_id', $state)
                                    ->where('liquidada', false)
                                    ->where('tipo_entrega', 'ENTREGA')
                                    ->get();

                                $repeaterData = $entregas->map(function ($entrega) {
                                    return [
                                        'entrega_id' => $entrega->id,
                                        'qq_liquidado' => $entrega->quintalaje_liquidable,
                                        'cantidad_sacos' => $entrega->cantidad_sacos,
                                        'peso_neto' => $entrega->peso_neto,
                                        'quintalaje_liquidable' => $entrega->quintalaje_liquidable,
                                        'monto_entrega' => 0,
                                    ];
                                })->toArray();

                                $set('detalle_liquidacion', $repeaterData);

                                // Obtener y establecer el crédito disponible del proveedor
                                $proveedor = \App\Models\Proveedor::find($state);
                                $creditoDisponible = $proveedor ? $proveedor->credito_disponible : 0;
                                $set('credito_disponible_proveedor', $creditoDisponible);
                            })
                            ->required()
                            ->reactive(),

                        Forms\Components\DatePicker::make('fecha_liquidacion')
                            ->label('Fecha de Liquidación')
                            ->required(),

                        Forms\Components\TextInput::make('usuario_liquida')
                            ->label('Usuario que Liquida')
                            ->default(auth()->id())
                            ->extraInputAttributes(['class' => 'pointer-events-none'])
                            ->readOnly(),

                        Forms\Components\Textarea::make('observacion')
                            ->label('Observaciones')
                            ->columnSpan('full') // Ocupa toda la fila
                            ->nullable(),
                    ]), // Fin de la sección Datos Generales

                Section::make('Detalle de Liquidación por Entregas') // Nueva sección para el repeater
                    ->visible(function (callable $get) {
                        // Sección visible SOLO si hay detalles de liquidación en el repeater
                        $detalleLiquidacion = $get('detalle_liquidacion') ?? [];
                        return !empty($detalleLiquidacion); // Visible si el array de detalles NO está vacío
                    })
                    ->schema([
                        Forms\Components\Repeater::make('detalle_liquidacion')
                            ->relationship('detalles')
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
                                    ->required(),

                                Forms\Components\TextInput::make('cantidad_sacos')
                                    ->label('Cantidad de Sacos')
                                    ->extraInputAttributes(['class' => 'pointer-events-none'])
                                    ->readOnly()
                                    ->required(),

                                Forms\Components\TextInput::make('peso_neto')
                                    ->label('Peso Neto')
                                    ->extraInputAttributes(['class' => 'pointer-events-none'])
                                    ->readOnly()
                                    ->required(),

                                Forms\Components\Hidden::make('qq_liquidado')
                                    ->default(function ($get) {
                                        return $get('quintalaje_liquidable');
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('quintalaje_liquidable')
                                    ->label('Quintalaje Liquidable')
                                    ->extraInputAttributes(['class' => 'pointer-events-none'])
                                    ->readOnly()
                                    ->required(),

                                Forms\Components\TextInput::make('monto_entrega')
                                    ->label('Monto de la Entrega')
                                    ->extraInputAttributes(['class' => 'pointer-events-none'])
                                    ->readOnly()
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        // Recalcular los totales al cambiar monto_entrega (dentro del repeater)
                                        self::recalcularTotales($set, $get);
                                    }),
                            ])
                            ->minItems(1)
                            ->createItemButtonLabel('Agregar Entrega')
                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                // Recalcular los totales al agregar, eliminar o modificar filas del repeater
                                self::recalcularTotales($set, $get);
                            }),
                    ]), // Fin de la sección Detalle de Liquidación

                Section::make('No hay resultados')
                    ->visible(function (callable $get) {
                        $detalleLiquidacion = $get('detalle_liquidacion') ?? [];
                        return empty($detalleLiquidacion);
                    }),

                Section::make('Cálculo de Liquidación y Créditos') // Nueva sección para montos y créditos
                    ->visible(function (callable $get) {
                        $detalleLiquidacion = $get('detalle_liquidacion') ?? [];
                        return !empty($detalleLiquidacion);
                    })
                    ->columns(3) // 3 columnas para alinear los campos
                    ->schema([
                        Forms\Components\TextInput::make('precio_liquidacion')
                            ->label('Precio de Liquidación por QQ')
                            ->required()
                            ->numeric()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                // Recalcular montos de entrega al cambiar precio_liquidacion
                                $detalle = $get('detalle_liquidacion') ?? [];
                                foreach ($detalle as $index => $row) {
                                    $quintalaje = $row['quintalaje_liquidable'] ?? 0;
                                    $set("detalle_liquidacion.{$index}.monto_entrega", $state * $quintalaje);
                                }
                                // Recalcular los totales (incluyendo monto bruto y neto)
                                self::recalcularTotales($set, $get);
                            }),

                        Forms\Components\TextInput::make('total_qq_liquidados')
                            ->label('Total QQ Liquidados')
                            ->extraInputAttributes(['class' => 'pointer-events-none'])
                            ->readOnly()
                            ->reactive()
                            ->required(),
                        Forms\Components\TextInput::make('tipo_cambio')
                            ->label('Tipo de Cambio')
                            ->required()
                            ->numeric(),

                        Forms\Components\TextInput::make('monto_bruto') // Monto Bruto (antes de créditos)
                            ->label('Monto Bruto')
                            ->extraInputAttributes(['class' => 'pointer-events-none'])
                            ->readOnly()
                            ->reactive()
                            ->required(),

                        Forms\Components\TextInput::make('credito_disponible_proveedor') // Campo informativo: Crédito Disponible
                            ->label('Crédito Disponible del Proveedor')
                            ->extraInputAttributes(['class' => 'pointer-events-none'])
                            ->readOnly()
                            ->hidden(function ($get) {
                                return $get('credito_disponible_proveedor') === 0;
                            })
                            ->dehydrated(false) // Evita que se guarde en la BD
                            ->columnSpan('full'), // Ocupa toda la fila para mejor visualización

                        Forms\Components\TextInput::make('monto_credito_aplicado') // Campo para ingresar Monto de Crédito a Aplicar
                            ->label('Monto de Crédito a Aplicar')
                            ->numeric()
                            ->default(0)
                            ->reactive()
                            ->debounce(500)
                            ->hidden(function ($get) {
                                return $get('credito_disponible_proveedor') === 0;
                            })
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                // Recalcular el monto neto al cambiar el monto de crédito aplicado
                                self::recalcularTotales($set, $get);
                            }),

                        Forms\Components\TextInput::make('monto_neto') // Monto Neto (después de créditos)
                            ->label('Monto Neto a Pagar')
                            ->extraInputAttributes(['class' => 'pointer-events-none'])
                            ->readOnly()
                            ->reactive()
                            ->required(),

                    ]), // Fin de la sección Cálculo de Liquidación y Créditos
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha_liquidacion')
                    ->label('Fecha de Liquidación')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('proveedor.nombrecompleto') // Muestra el nombre del proveedor
                    ->label('Proveedor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('usuario.name') // Muestra el nombre del usuario que liquida
                    ->label('Usuario Liquida')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_qq_liquidados')
                    ->label('Total QQ Liquidados')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monto_neto') // Muestra el Monto Neto en la tabla
                    ->label('Monto Neto') // Etiqueta actualizada a Monto Neto
                    ->sortable()
                    ->formatStateUsing(fn($state) => 'C$ ' . number_format($state, 2)),
                Tables\Columns\TextColumn::make('estado')
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

    // Función reutilizable para recalcular totales (monto bruto, neto, total qq)
    private static function recalcularTotales(callable $set, callable $get): void
    {
        $detalle = $get('detalle_liquidacion') ?? [];
        $monto_bruto = collect($detalle)->sum(fn($row) => (float) ($row['monto_entrega'] ?? 0));
        $total_qq = collect($detalle)->sum(fn($row) => (float) ($row['quintalaje_liquidable'] ?? 0));
        $monto_credito_aplicado = $get('monto_credito_aplicado') ?? 0;
        $monto_neto = max(0, $monto_bruto - $monto_credito_aplicado); // Asegura que el monto neto no sea negativo
        $set('monto_bruto', $monto_bruto);
        $set('total_qq_liquidados', $total_qq);
        $set('monto_neto', $monto_neto);
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
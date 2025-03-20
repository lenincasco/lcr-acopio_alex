<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentaResource\Pages;
use App\Models\Venta;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use App\Traits\RegularPermissions;

class VentaResource extends Resource
{
    use RegularPermissions;
    protected static ?string $model = Venta::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombrecompleto') // Relación con la tabla de clientees
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('fecha_venta')
                    ->label('Fecha venta')
                    ->required()
                    ->extraInputAttributes([
                        'style' => 'width: 150px;',
                    ])
                    ->date('d-M-y'),

                Section::make('Datos del café')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('tipo_cafe')
                            ->label('Tipo de Café')
                            ->options([
                                'UVA' => 'UVA',
                                'PERGAMINO' => 'PERGAMINO',
                                'MARA' => 'MARA',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($set, $state) {
                                // Consulta a la base de datos para obtener los valores únicos de humedad
                                $humedades = \App\Models\Inventario::query()
                                    ->where('tipo_cafe', $state)
                                    ->groupBy('humedad')
                                    ->pluck('humedad')
                                    ->toArray();

                                // Convertir a formato key => value (por ejemplo, '5' => '5%')
                                $options = collect($humedades)
                                    ->mapWithKeys(function ($value) {
                                    return [$value => $value];
                                })
                                    ->toArray();

                                // Guardar estas opciones en un estado temporal
                                $set('humedadOptions', $options);
                                // Limpiar la selección anterior de humedad (si hubiera)
                                $set('humedad', null);
                            })
                            ->required(),

                        Forms\Components\Select::make('humedad')
                            ->label('Humedad')
                            ->options(function (callable $get) {
                                // Usamos las opciones previamente definidas en 'tipo_cafe'
                                return $get('humedadOptions') ?? [];
                            })
                            ->searchable()
                            ->reactive()
                            ->required(),
                    ]),

                Section::make('Peso')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('cantidad_sacos')
                            ->label('Cantidad de Sacos')
                            ->required()
                            ->reactive()
                            ->regex('/^\d+(\.\d{1,2})?$/')
                            ->numeric()
                            ->mask(RawJs::make(<<<'JS'
    $input.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
JS))
                            ->debounce(500)
                            ->afterStateUpdated(function ($set, $get, $state) {
                                $tipoCafe = $get('tipo_cafe'); // Obtener el tipo de café seleccionado
                                $stockDisponible = \App\Models\Inventario::where('tipo_cafe', $tipoCafe)->where('humedad', $get('humedad'))->sum('cantidad_sacos'); // Consultar la cantidad disponible
                    
                                $tara = ceil($state / 2); // Cada saco es media libra, este es el peso total en libras
                                $set('tara_saco', $tara / 100);//Este en quintal
                    
                                if ($get('tipo_cafe') == '') {
                                    $set('cantidad_sacos', 0);
                                    Notification::make()
                                        ->title('Tipo de Café no seleccionado')
                                        ->body('Por favor, seleccione un tipo de café antes de ingresar cantidades.')
                                        ->warning()
                                        ->send();
                                }
                                if ($state > $stockDisponible && $get('tipo_cafe') != '') {
                                    $set('cantidad_sacos', $stockDisponible); // Ajustar la cantidad al máximo disponible
                                    Notification::make()
                                        ->title('Stock insuficiente')
                                        ->body("Solo hay $stockDisponible sacos disponibles para el tipo de café seleccionado.")
                                        ->danger()
                                        ->send();
                                }
                            }),

                        Forms\Components\TextInput::make('peso_bruto')
                            ->label('Peso Bruto')
                            ->required()
                            ->regex('/^\d+(\.\d{1,2})?$/')
                            ->numeric()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($set, $state, $get) {
                                $tara = $get('tara_saco');
                                if ($state && $tara) {
                                    $pesoNeto = $state - $tara;
                                    $set('peso_neto', $pesoNeto);

                                    $tipoCafe = $get('tipo_cafe'); // Obtener el tipo de café seleccionado
                                    $stockDisponible = \App\Models\Inventario::where('tipo_cafe', $tipoCafe)->where('humedad', $get('humedad'))->sum('peso_neto'); // Consultar la cantidad disponible
                                    if ($pesoNeto > $stockDisponible) {
                                        $set('peso_neto', round($stockDisponible, 3));
                                        $set('peso_bruto', round($stockDisponible + $tara, 3));
                                        Notification::make()
                                            ->title('Stock insuficiente')
                                            ->body("Solo hay $stockDisponible QQ disponibles para el tipo de café seleccionado.")
                                            ->danger()
                                            ->send();
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('tara_saco')
                            ->label('Tara total(En quintales)')
                            ->reactive()
                            ->disabled()
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('peso_neto')
                            ->label('PESO NETO')
                            ->reactive()
                            ->afterStateUpdated(function ($set, $get) {
                                self::recalcularTotales($set, $get);
                            })
                            ->disabled()
                            ->dehydrated(true),
                    ]),
                Section::make('Precios')
                    ->columns(5)
                    ->schema([
                        Forms\Components\TextInput::make('precio_unitario')
                            ->label('Precio Unitario')
                            ->required()
                            ->numeric()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($set, $get) {
                                self::recalcularTotales($set, $get);
                            }),

                        Forms\Components\TextInput::make('iva')
                            ->label('IVA %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(99)
                            ->default(0)
                            ->mask(RawJs::make(<<<'JS'
    $input.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
JS))
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($set, $get) {
                                self::recalcularTotales($set, $get);
                            }),
                        Forms\Components\TextInput::make('tipo_cambio')
                            ->label('Tipo de Cambio a USD')
                            ->numeric()
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(function ($set, $get) {
                                self::recalcularTotales($set, $get);
                            }),

                        Forms\Components\TextInput::make('monto_bruto')
                            ->label('Monto Bruto')
                            ->disabled()
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('monto_neto')
                            ->label('MONTO NETO C$')
                            ->disabled()
                            ->dehydrated(true),
                    ]),

                Forms\Components\Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombrecompleto')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('fecha_venta')
                    ->label('Fecha de Venta')
                    ->sortable()
                    ->date('d-m-yy'),

                Tables\Columns\TextColumn::make('cantidad_sacos')
                    ->label('Sacos')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('peso_neto')
                    ->label('Peso Neto (Quintales)')
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(fn(string $state): string => number_format($state, 2)),
                Tables\Columns\TextColumn::make('tipo_cafe')
                    ->label('Tipo de Café'),
                Tables\Columns\TextColumn::make('humedad')
                    ->label('Humedad'),
                Tables\Columns\TextColumn::make('monto_neto')
                    ->label('Monto Neto')
                    ->money('NIO', locale: 'es_NI')
                    ->sortable()
                    ->alignRight(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->visible(fn() => auth()->user()->hasRole('super_admin')),
            ]);
    }

    private static function recalcularTotales(callable $set, callable $get): void
    {
        $precioUnitario = (float) ($get('precio_unitario') ?? 0);
        $pesoNeto = (float) ($get('peso_neto') ?? 0);
        $iva = (float) ($get('iva') ?? 0);

        $montoBruto = $precioUnitario * $pesoNeto;
        $montoNeto = $montoBruto + ($montoBruto * ($iva / 100)); // IVA calculado sobre monto_bruto

        $set('monto_bruto', number_format($montoBruto, 2, '.', ''));
        $set('monto_neto', number_format($montoNeto, 2, '.', ''));
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
            'index' => Pages\ListVentas::route('/'),
            'create' => Pages\CreateVenta::route('/create'),
            'edit' => Pages\EditVenta::route('/{record}/edit'),
        ];
    }
}

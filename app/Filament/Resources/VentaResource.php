<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentaResource\Pages;
use App\Models\Venta;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombrecompleto') // Relación con la tabla de clientees
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nombrecompleto')
                            ->label('Nombre')
                            ->required()
                    ]),

                Forms\Components\DatePicker::make('fecha_venta')
                    ->label('Fecha venta')
                    ->required()
                    ->date('d-M-y'),

                Forms\Components\TextInput::make('cantidad_sacos')
                    ->label('Cantidad de Sacos')
                    ->required()
                    ->reactive()
                    ->regex('/^\d+(\.\d{1,2})?$/')
                    ->numeric()
                    ->afterStateUpdated(function ($set, $state) {
                        // Calcular la tara por saco
                        if ($state) {
                            // Redondear hacia arriba la tara
                            $tara = ceil($state / 2); // Cada saco es media libra
                            $set('tara_saco', $tara / 100); // Asigna la tara calculada al campo 'tara'
                        }
                    }),

                Forms\Components\TextInput::make('tara_saco')
                    ->label('Tara por saco')
                    ->required()
                    ->reactive()
                    ->readOnly(),

                Forms\Components\TextInput::make('peso_bruto')
                    ->label('Peso Bruto')
                    ->required()
                    ->regex('/^\d+(\.\d{1,2})?$/')
                    ->numeric()
                    ->reactive()
                    ->afterStateUpdated(function ($set, $state, $get) {
                        $tara = $get('tara_saco');
                        if ($state && $tara) {
                            $set('peso_neto', $state - $tara);
                        }
                    }),

                Forms\Components\TextInput::make('humedad')
                    ->label('Humedad %')
                    ->required(),

                Forms\Components\TextInput::make('imperfeccion')
                    ->label('Imperfección %')
                    ->required(),

                Forms\Components\TextInput::make('peso_neto')
                    ->label('Peso Neto')
                    ->readOnly()
                    ->reactive()
                    ->afterStateUpdated(function ($set, $get) {
                        self::recalcularTotales($set, $get);
                    }),

                Forms\Components\TextInput::make('tipo_cambio')
                    ->label('Tipo de Cambio')
                    ->numeric()
                    ->nullable()
                    ->reactive()
                    ->afterStateUpdated(function ($set, $get) {
                        self::recalcularTotales($set, $get);
                    }),

                Forms\Components\TextInput::make('precio_unitario')
                    ->label('Precio Unitario')
                    ->numeric()
                    ->reactive()
                    ->afterStateUpdated(function ($set, $get) {
                        self::recalcularTotales($set, $get);
                    }),

                Forms\Components\TextInput::make('monto_bruto')
                    ->label('Monto Bruto')
                    ->numeric()
                    ->readOnly(),

                Forms\Components\TextInput::make('iva')
                    ->label('IVA')
                    ->numeric()
                    ->reactive()
                    ->afterStateUpdated(function ($set, $get) {
                        self::recalcularTotales($set, $get);
                    }),

                Forms\Components\TextInput::make('monto_neto')
                    ->label('Monto Neto')
                    ->numeric()
                    ->readOnly(),

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
                Tables\Columns\TextColumn::make('monto_neto')
                    ->label('Monto Neto')
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(fn(string $state): string => number_format($state, 2)),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(function ($record): bool {
                        return $record->liquidada == true;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function recalcularTotales(callable $set, callable $get): void
    {
        $precioUnitario = $get('precio_unitario') ?? 0;
        $pesoNeto = $get('peso_neto') ?? 0;
        $iva = $get('iva') ?? 0;

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

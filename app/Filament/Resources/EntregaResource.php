<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntregaResource\Pages;
use App\Models\Entrega;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EntregaResource extends Resource
{
    protected static ?string $model = Entrega::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Proveedor')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('proveedor_id')
                            ->label('Proveedor')
                            ->relationship('proveedor', 'nombrecompleto') // Relación con la tabla de proveedores
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nombrecompleto')
                                    ->label('Nombre')
                                    ->required()
                            ]),
                        Forms\Components\Select::make('tipo_entrega')
                            ->label('Tipo de Entrega')
                            ->options([
                                'COMPRA' => 'COMPRA',
                                'ENTREGA' => 'ENTREGA',
                            ])
                            ->default('ENTREGA')
                            ->required(),
                        Forms\Components\DatePicker::make('fecha_entrega')
                            ->label('Fecha entrega')
                            ->required()
                            ->extraInputAttributes([
                                'style' => 'width: 150px;',
                            ])
                            ->date('d-M-y'),
                    ]),

                Section::make('Datos del café')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('tipo_cafe')
                            ->label('Tipo de Café')
                            ->options([
                                'UVA' => 'UVA',
                                'PERGAMINO' => 'PERGAMINO',
                                'MARA' => 'MARA'
                            ])
                            ->required(),
                        Select::make('humedad')
                            ->label('Humedad')
                            ->required()
                            ->options([
                                'OREADO' => 'OREADO',
                                'HUMEDO' => 'HÚMEDO',
                                'MOJADO' => 'MOJADO',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($set, $get): void {
                                self::calcularOroBruto($set, $get);
                            }),
                        Select::make('calidad')
                            ->label('Calidad')
                            ->required()
                            ->options([
                                'PRIMERA' => 'PRIMERA',
                                'EXPORTABLE' => 'EXPORTABLE',
                                'IMPERFECTO' => 'IMPERFECTO',
                            ]),
                    ]),

                Section::make('Cantidades')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cantidad_sacos')
                            ->label('Cantidad de Sacos')
                            ->required()
                            ->regex('/^\d+(\.\d{1,2})?$/')
                            ->numeric(),
                        Forms\Components\TextInput::make('peso_bruto')
                            ->label('Peso Bruto')
                            ->required()
                            ->regex('/^\d+(\.\d{1,2})?$/')
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($set, $state, $get) {
                                // Calcular la tara por saco
                                if ($state) {
                                    // Redondear hacia arriba la tara
                                    $tara = ceil($state / 2); // Cada saco es media libra
                                    $set('tara_saco', $tara / 100); // Asigna la tara calculada al campo 'tara'
                                    self::calcularOroBruto($set, $get);
                                }
                            }),
                        Forms\Components\TextInput::make('precio_compra')
                            ->label('Precio compra(Por quintal)')
                            ->required()
                            ->numeric(),
                    ]),

                Section::make('Totales')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('quintalaje_liquidable')
                            ->label('Quintalaje Liquidable')
                            ->extraInputAttributes(['class' => 'pointer-events-none'])
                            ->readOnly(),
                        Forms\Components\TextInput::make('tara_saco')
                            ->label('Tara por sacos')
                            ->extraInputAttributes(['class' => 'pointer-events-none'])
                            ->readOnly(),
                        Forms\Components\TextInput::make('peso_neto')
                            ->label('Peso Neto')
                            ->extraInputAttributes(['class' => 'pointer-events-none'])
                            ->readOnly()
                            ->reactive(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proveedor.nombrecompleto')
                    ->label('Proveedor')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('fecha_entrega')
                    ->label('Fecha de Entrega')
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

                Tables\Columns\TextColumn::make('quintalaje_liquidable')
                    ->label('Quintalaje liquidable')
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
    private static function calcularOroBruto(callable $set, callable $get): void
    {
        $humedad = $get('humedad');
        $tara = $get('tara_saco');
        $pesoBruto = $get('peso_bruto');
        if ($humedad == 'OREADO') {
            $oroBruto = $pesoBruto / 2;
            $set('quintalaje_liquidable', $oroBruto);
        }
        if ($humedad == 'HUMEDO') {
            $oroBruto = ($pesoBruto * 0.92) / 2;
            $set('quintalaje_liquidable', $oroBruto);
        }
        if ($humedad == 'MOJADO') {
            $oroBruto = ($pesoBruto * 0.86) / 2;
            $set('quintalaje_liquidable', $oroBruto);
        }
        $set('peso_neto', $pesoBruto - $tara);
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
            'index' => Pages\ListEntregas::route('/'),
            'create' => Pages\CreateEntrega::route('/create'),
            'edit' => Pages\EditEntrega::route('/{record}/edit'),
        ];
    }
}

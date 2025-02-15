<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntregaResource\Pages;
use App\Models\Entrega;
use Filament\Forms;
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
                Forms\Components\Select::make('tipo_cafe')
                    ->label('Tipo de Café')
                    ->options([
                        'UVA' => 'Uva',
                        'PERGAMINO' => 'Pergamino',
                        'MARA' => 'Mara'
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('fecha_entrega')
                    ->label('Fecha entrega')
                    ->required()
                    ->date('d-M-y'),

                Forms\Components\TextInput::make('cantidad_sacos')
                    ->label('Cantidad de Sacos')
                    ->required()
                    ->reactive()
                    ->regex('/^\d+(\.\d{1,2})?$/')
                    ->numeric()
                    ->debounce(500)
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
                    ->debounce(500)
                    ->afterStateUpdated(function ($set, $state, $get) {
                        $tara = $get('tara_saco');
                        if ($state && $tara) {
                            $set('peso_neto', $state - $tara);
                        }
                    }),

                Forms\Components\TextInput::make('humedad')
                    ->label('Humedad %')
                    ->required()
                    ->reactive()
                    ->debounce(500),

                Forms\Components\TextInput::make('imperfeccion')
                    ->label('Imperfección %')
                    ->required()
                    ->reactive()
                    ->debounce(500),

                Forms\Components\TextInput::make('peso_neto')
                    ->label('Peso Neto')
                    ->readOnly()
                    ->reactive(),

                Forms\Components\TextInput::make('quintalaje_liquidable')
                    ->label('Quintalaje Liquidable')
                    ->required()
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
                    ->label('Peso Liquidable (Quintales)')
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

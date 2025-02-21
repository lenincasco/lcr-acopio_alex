<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbonoResource\Pages;
use App\Filament\Resources\AbonoResource\RelationManagers;
use App\Models\Abono;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Schema;

class AbonoResource extends Resource
{
    protected static ?string $model = Abono::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->columns(3)
                    ->Schema([
                        Forms\Components\Select::make('prestamo_id')
                            ->label('Préstamo')
                            ->options(function () {
                                return \App\Models\Prestamo::with('proveedor')
                                    ->get()
                                    ->mapWithKeys(function ($prestamo) {
                                        // Se utiliza el nombre completo del proveedor para la opción
                                        return [$prestamo->id => $prestamo->proveedor->nombrecompleto];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                $prestamo = \App\Models\Prestamo::with('proveedor')->find($state);
                                if ($prestamo) {
                                    // Se asigna el saldo actual del préstamo al estado "saldo_anterior"
                                    $set('saldo_anterior', $prestamo->saldo);
                                } else {
                                    $set('saldo_anterior', $state);
                                }
                            }),
                        Forms\Components\TextInput::make('monto')
                            ->label('Monto')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                $set('nuevo_saldo', $get('saldo_anterior') - $state);
                            }),
                        Forms\Components\DatePicker::make('fecha_pago')
                            ->label('Fecha de Pago')
                            ->extraInputAttributes([
                                'style' => 'width: 150px;',
                            ])
                            ->required(),
                    ]),

                Split::make([
                    Section::make('')
                        ->schema([
                            Forms\Components\Textarea::make('observaciones')
                                ->label('Observaciones')
                                ->rows(4)
                                ->nullable(),
                        ]),
                    Section::make('')
                        ->schema([
                            Forms\Components\Placeholder::make('saldo_anterior_placeholder')
                                ->label('Saldo anterior')
                                ->content(fn(callable $get) => $get('saldo_anterior') !== null ? $get('saldo_anterior') : 'N/A'),
                            Forms\Components\Placeholder::make('nuevo_saldo_placeholder')
                                ->label('Nuevo saldo')
                                ->content(fn(callable $get) => $get('nuevo_saldo') !== null ? $get('nuevo_saldo') : 'N/A'),
                        ])->grow(),
                ])->from('md')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListAbonos::route('/'),
            'create' => Pages\CreateAbono::route('/create'),
            'edit' => Pages\EditAbono::route('/{record}/edit'),
        ];
    }
}

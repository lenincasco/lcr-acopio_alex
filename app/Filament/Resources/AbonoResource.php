<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbonoResource\Pages;
use App\Models\Abono;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Schema;

class AbonoResource extends Resource
{
    protected static ?string $model = Abono::class;
    protected static ?string $navigationGroup = 'Finanzas';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->columns(3)
                    ->Schema([
                        Forms\Components\Select::make('prestamo_id')
                            ->label('Deudor')
                            ->options(function () {
                                return \App\Models\Prestamo::with('proveedor')
                                    ->get()
                                    ->mapWithKeys(function ($prestamo) {
                                        return [$prestamo->id => $prestamo->proveedor->nombrecompleto];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                $prestamoId = $get('prestamo_id');
                                $prestamo = \App\Models\Prestamo::with('proveedor')->find($prestamoId);
                                $interes = $prestamo->interes;
                                $set('interes', $interes . '%');//info only
                            }),
                        Forms\Components\DatePicker::make('fecha_pago')
                            ->label('Fecha de Pago')
                            ->extraInputAttributes([
                                'style' => 'width: 150px;',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                self::calcularTotales($get, $set);
                            }),
                        Forms\Components\TextInput::make('monto')
                            ->label('Monto')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                self::calcularTotales($get, $set);
                            }),
                    ]),
                Section::make('')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('intereses')
                            ->label('Intereses')
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('abono_capital')
                            ->label('Abono al capital')
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('saldo')
                            ->label('Saldo')
                            ->disabled()
                            ->dehydrated(true),
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
                            Forms\Components\Placeholder::make('dias_diff_placeholder')
                                ->label('Dias desde el ult. Pago')
                                ->content(fn(callable $get) => $get('dias_diff') !== null ? $get('dias_diff') : '---'),
                            Forms\Components\Placeholder::make('saldo_anterior_placeholder')
                                ->label('Saldo anterior')
                                ->content(fn(callable $get) => $get('saldo_anterior') !== null ? $get('saldo_anterior') : '---'),
                            Forms\Components\Placeholder::make('interes_placeholder')
                                ->label('Interés')
                                ->content(fn(callable $get) => $get('interes') !== null ? $get('interes') : '---')
                        ])->grow(),
                ])->from('md')
                    ->columnSpanFull(),
            ]);
    }

    private static function calcularTotales($get, $set): void
    {
        $prestamoId = $get('prestamo_id');
        $fechaPago = $get('fecha_pago');
        $monto = (float) $get('monto');

        if (!$prestamoId) {
            Notification::make()
                ->title('Campo requerido')
                ->body('Selecciona el Proveedor al cual se le ha asignado el préstamo.')
                ->warning()
                ->send();
            $set('monto', null);
            return;
        }
        if ($fechaPago == '') {
            Notification::make()
                ->title('Campo requerido')
                ->body('Selecciona la fecha de pago')
                ->warning()
                ->send();
            $set('monto', null);
            return;
        }
        if (!$monto) {
            return;
        }
        $prestamo = \App\Models\Prestamo::with('proveedor')->find($prestamoId);
        $fechaUltimoPago = $prestamo->fecha_ultimo_pago;


        if (!$fechaUltimoPago) {
            $fechaUltimoPago = $prestamo->fecha_desembolso;
        }
        if ($prestamo) {
            $diasDiff = Carbon::parse($fechaUltimoPago)->diffInDays(Carbon::parse($fechaPago));

            $intereses = (($prestamo->monto * $prestamo->interes / 100) / 360) * $diasDiff;
            $abonoCapital = floatval($monto) - $intereses;

            $set('saldo_anterior', $prestamo->saldo);
            $set('intereses', round($intereses, precision: 2));
            $set('dias_diff', round($diasDiff));
            $set('abono_capital', round($abonoCapital, 2));
            $set('saldo', round($prestamo->saldo - $abonoCapital, 2));
            $set('nuevo_saldo', 'C$' . round($prestamo->saldo - $abonoCapital, 2));
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('monto')
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

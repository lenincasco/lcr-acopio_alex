<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbonoResource\Pages;
use App\Models\Abono;
use App\Models\Prestamo;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Log;
use App\Traits\RegularPermissions;

class AbonoResource extends Resource
{
    use RegularPermissions;
    protected static ?string $model = Abono::class;
    protected static ?string $navigationGroup = 'Finanzas';
    protected static ?int $navigationSort = 4;

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
                            ->relationship('prestamo.proveedor', 'nombrecompleto')
                            ->searchable()
                            ->required()
                            ->disabled(fn($livewire): bool => filled($livewire->record))
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
                            ->disabled(fn($livewire): bool => filled($livewire->record))
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                Log::info('fecha pago abono:' . $state);
                                self::calcularTotales($get, $set);
                            }),
                        Forms\Components\TextInput::make('monto')
                            ->label('Monto')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->hidden(fn($livewire): bool => filled($livewire->record))
                            ->debounce(750)
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                self::calcularTotales($get, $set);
                            }),
                    ]),
                Section::make('')
                    ->columns(12)
                    ->columnSpan('full')
                    ->schema([
                        Forms\Components\TextInput::make('dias_diff')
                            ->label('Dias desde el ult. Pago')
                            ->columnSpan(2)
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('intereses')
                            ->label('Intereses')
                            ->columnSpan(2)
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('abono_capital')
                            ->label('Abono al capital')
                            ->columnSpan(2)
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('saldo')
                            ->label('Saldo')
                            ->columnSpan(2)
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
                            Forms\Components\Placeholder::make('saldo_anterior_placeholder')
                                ->label('Saldo anterior')
                                ->content(fn(callable $get) => $get('saldo_anterior') !== null ? $get('saldo_anterior') : '---'),
                            Forms\Components\Placeholder::make('interes_placeholder')
                                ->label('Interés')
                                ->content(fn(callable $get) => $get('interes') !== null ? $get('interes') : '---')
                        ])->grow(),
                ])->from('md')
                    ->columnSpanFull(),

                Section::make('Anular abono')
                    ->columns(12)
                    ->columnSpan('full')
                    ->hidden(function ($livewire, $get): bool {
                        if (!$livewire->record)
                            return true;
                        // Obtener el abono actual en edición
                        $currentAbono = $livewire->record;
                        // Verificar si es el último abono del préstamo
                        $isLast = Abono::where('prestamo_id', $currentAbono->prestamo_id)
                            ->latest('created_at')
                            ->value('id') === $currentAbono->id;

                        return !$isLast; // Ocultar si NO es el último
            
                    })
                    ->schema([
                        Forms\Components\Select::make('estado')
                            ->label('¿Anular abono?')
                            ->columnSpan(2)
                            ->default('ACTIVO') // Asegurar un valor inicial
                            ->options([
                                'ACTIVO' => 'NO',
                                'ANULADO' => 'SI',
                            ])
                            ->reactive(),

                        Forms\Components\Textarea::make('razon_anula')
                            ->label('Razón de la anulación')
                            ->columnSpan(6)
                            ->required()
                            ->hidden(fn($get) => $get('estado') !== 'ANULADO'),
                        Hidden::make('user_id')
                            ->default(auth()->id())
                            ->dehydrated(true),
                        Hidden::make('fecha_anula')
                            ->default(now())
                            ->dehydrated(true),
                    ]),
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
            $saldoAnterior = $prestamo->saldo;
            $diasDiff = Carbon::parse($fechaUltimoPago)->diffInDays(Carbon::parse($fechaPago));
            if ($diasDiff < 0)
                $diasDiff = 0;
            $intereses = (($prestamo->monto * $prestamo->interes / 100) / 360) * $diasDiff;
            $abonoCapital = floatval($monto) - $intereses;
            if ($monto > $saldoAnterior + $intereses) {
                $monto = $saldoAnterior + $intereses;
                $set('monto', round($monto, 2));
                $intereses = (($prestamo->monto * $prestamo->interes / 100) / 360) * $diasDiff;
                $abonoCapital = floatval($monto) - $intereses;
            }

            $set('saldo_anterior', $saldoAnterior);
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
                Tables\Columns\TextColumn::make('prestamo.proveedor.nombrecompleto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('abono_capital')
                    ->label('Abono al capital')
                    ->searchable()
                    ->money('NIO', locale: 'es_NI'),
                Tables\Columns\TextColumn::make('intereses')
                    ->searchable()
                    ->label('Intereses')
                    ->money('NIO', locale: 'es_NI'),
                Tables\Columns\TextColumn::make('fecha_pago')
                    ->dateTime('d-m-Y')
                    ->searchable()
                    ->label('Fecha de pago'),
                Tables\Columns\TextColumn::make('estado')
                    ->color(fn($record) => $record->estado === 'ANULADO' ? 'danger' : ''),
                Tables\Columns\TextColumn::make('prestamo.saldo')
                    ->label('Saldo del préstamo')
                    ->money('NIO', locale: 'es_NI'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->visible(fn() => auth()->user()->hasRole('super_admin')),
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

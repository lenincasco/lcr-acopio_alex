<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrestamoResource\Pages;
use App\Models\Prestamo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\Section;

use Carbon\Carbon;

class PrestamoResource extends Resource
{
    protected static ?string $model = Prestamo::class;
    protected static ?string $navigationGroup = 'Finanzas';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos Generales de Prestamo')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('proveedor_id')
                            ->label('Proveedor')
                            ->relationship('proveedor', 'nombrecompleto') // Asegúrate de que la relación esté definida en el modelo
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('plazo_meses')
                            ->label('Plazo (meses)')
                            ->placeholder('Cantidad')
                            ->numeric()
                            ->regex('/^\d+(\.\d{1,3})?$/')
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function ($set, $get) {
                                self::caculate($set, $get);
                                self::calculateDueDate($set, $get);
                            }),
                        Forms\Components\TextInput::make('interes')
                            ->label('Interés (%)')
                            ->placeholder('Ejemplo: 15')
                            ->required()
                            ->regex('/^\d+(\.\d{1,3})?$/')
                            ->numeric()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($set, $get) {
                                self::caculate($set, $get);
                            }),
                        Forms\Components\TextInput::make('monto')
                            ->label('Monto C$')
                            ->placeholder('Monto en córdobas')
                            ->required()
                            ->regex('/^\d+(\.\d{1,3})?$/')
                            ->numeric()
                            ->reactive()
                            ->debounce(750)
                            ->afterStateUpdated(function ($set, $get) {
                                self::caculate($set, $get);
                            }),

                        Forms\Components\DatePicker::make('fecha_desembolso')
                            ->label('Fecha de Desembolso')
                            ->displayFormat('d-m-Y')
                            ->extraInputAttributes([
                                'style' => 'width: 150px;',
                            ])
                            ->required()
                            ->reactive()
                            ->debounce(500)
                            ->afterStateUpdated(function ($set, $get) {
                                self::calculateDueDate($set, $get);
                            }),
                        Forms\Components\DatePicker::make('fecha_vencimiento')
                            ->label('Fecha de Vencimiento')
                            ->displayFormat('d-m-Y')
                            ->extraInputAttributes([
                                'style' => 'width: 150px;',
                            ])
                            ->required()
                            ->reactive()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('volumen_estimado')
                            ->label('Volumen Estimado (quintales)')
                            ->placeholder('Cantidad')
                            ->regex('/^\d+(\.\d{1,4})?$/')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('precio_referencia')
                            ->label('Precio de Referencia')
                            ->regex('/^\d+(\.\d{1,4})?$/')
                            ->numeric()
                            ->placeholder('Precio por quintal')
                            ->required(),
                        Forms\Components\TextInput::make('tipo_cambio')
                            ->label('Tipo de Cambio')
                            ->regex('/^\d+(\.\d{1,4})?$/')
                            ->numeric()
                            ->default(36.62),
                    ]),

                Section::make('Totales')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('monto_interes')
                            ->label('Monto Interes C$')
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('monto_total')
                            ->label('Monto Total C$')
                            ->disabled()
                            ->dehydrated(true),
                    ]),
            ]);
    }

    private static function caculate($set, $get): void
    {
        $monto = $get('monto') ?? 0;
        $plazo = $get('plazo_meses') ?? 0;
        $interes = $get('interes') ?? 0;
        if ($interes && $monto) {
            $monto == '' ? $monto = 0 : $monto;
            $plazo == '' ? $plazo = 0 : $plazo;
            $interes == '' ? $interes = 0 : $interes;
            $montoInteres = ($monto * $interes) / 100; // Calcular el monto de interés
            $set('monto_interes', $montoInteres);
            $set('monto_total', $montoInteres + $monto);
        }
    }

    protected static function calculateDueDate($set, $get)
    {
        $plazo = (int) $get('plazo_meses');
        $fechaDesembolso = $get('fecha_desembolso');

        if ($fechaDesembolso && $plazo > 0) {
            $fechaVencimiento = \Carbon\Carbon::parse($fechaDesembolso)
                ->addMonths($plazo)
                ->toDateString(); // Devuelve la fecha en formato Y-m-d
            $set('fecha_vencimiento', $fechaVencimiento);
        }
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proveedor.nombrecompleto')
                    ->label('Proveedor')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('monto') // Monto del préstamo
                    ->label('Monto Desembolso C$')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                Tables\Columns\TextColumn::make('monto_interes') // Monto de interés
                    ->label('Monto de Interés C$')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                Tables\Columns\TextColumn::make('saldo') // Monto total
                    ->label('Saldo')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                Tables\Columns\TextColumn::make('interes') // Interés
                    ->label('Interés (%)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plazo_meses') // Plazo en meses
                    ->label('Plazo (meses)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_ultimo_pago')
                    ->label('Fecha de ultimo pago'),
                Tables\Columns\TextColumn::make('fecha_desembolso')
                    ->label('Fecha de Desembolso')
                    ->date('d-m-yy')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Fecha de Vencimiento')
                    ->date('d-m-yy')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at') // Fecha de creación
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at') // Fecha de actualización
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Aquí puedes agregar filtros si es necesario
            ])
            ->actions([
                ActionGroup::make([ // Agrupa las acciones en un menú
                    Action::make('verPagare')
                        ->label('Vista previa del Pagaré')
                        ->icon('heroicon-o-eye')
                        ->modalHeading('Vista Previa del Pagaré')
                        ->modalSubmitActionLabel('Imprimir')
                        ->modalWidth('7xl')
                        ->modalSubmitAction(false)
                        ->modalContent(fn($record) => view('single.modalpagare', compact('record'))),
                    Action::make('verPagare')
                        ->label('Ir a la Página del Pagaré')
                        ->icon('heroicon-o-document')
                        ->url(fn($record) => route('single.pagare', $record->id)) // Asegúrate de que la ruta existe
                        ->openUrlInNewTab(), // Opcional, abre la URL en una nueva pestaña
                ])
                    ->tooltip('Ver PAGARÉ')
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->dropdown()
                    ->color('primary')
            ])
            ->recordUrl(null)
            ->actionsPosition(ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(), // Acción para eliminar en bloque
                ]),
            ]);
    }

    public static function verPagare($record)
    {
        $prestamo = Prestamo::with('proveedor')->findOrFail($record);
        $pdf = Pdf::loadView('single.pagare', compact('prestamo'))->setPaper('legal', 'portrait');

        return $pdf->stream('pagare-id-' . $prestamo->id . '.pdf');
    }

    // public static function verPagare($record)
    // {
    //     $prestamo = Prestamo::with('proveedor')->findOrFail($record);
    //     return view('single.pagare', compact('prestamo'));
    // }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrestamos::route('/'),
            'create' => Pages\CreatePrestamo::route('/create'),
            'edit' => Pages\EditPrestamo::route('/{record}/edit'),
        ];
    }
}

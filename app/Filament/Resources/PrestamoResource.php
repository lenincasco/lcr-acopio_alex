<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrestamoResource\Pages;
use App\Models\Prestamo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use Carbon\Carbon;

class PrestamoResource extends Resource
{
    protected static ?string $model = Prestamo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship('proveedor', 'nombrecompleto') // Asegúrate de que la relación esté definida en el modelo
                    ->required(),
                Forms\Components\TextInput::make('plazo_meses')
                    ->label('Plazo (meses)')
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
                    ->required()
                    ->regex('/^\d+(\.\d{1,3})?$/')
                    ->numeric()
                    ->reactive()
                    ->debounce(500)
                    ->afterStateUpdated(function ($set, $get) {
                        self::caculate($set, $get);
                    }),
                Forms\Components\TextInput::make('monto_interes')
                    ->label('Monto Interes C$')
                    ->required()
                    ->extraInputAttributes(['class' => 'pointer-events-none'])
                    ->readOnly(),
                Forms\Components\TextInput::make('monto_total')
                    ->label('Monto Total C$')
                    ->required()
                    ->extraInputAttributes(['class' => 'pointer-events-none'])
                    ->readOnly(),
                Forms\Components\DatePicker::make('fecha_desembolso')
                    ->label('Fecha de Desembolso')
                    ->displayFormat('d-m-Y')
                    ->required()
                    ->reactive()
                    ->debounce(500)
                    ->afterStateUpdated(function ($set, $get) {
                        self::calculateDueDate($set, $get);
                    }),

                Forms\Components\TextInput::make('volumen_estimado')
                    ->label('Volumen Estimado (quintales)')
                    ->regex('/^\d+(\.\d{1,4})?$/')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('precio_referencia')
                    ->label('Precio de Referencia')
                    ->regex('/^\d+(\.\d{1,4})?$/')
                    ->numeric()
                    ->required(),


                Forms\Components\DatePicker::make('fecha_vencimiento')
                    ->label('Fecha de Vencimiento')
                    ->displayFormat('d-m-Y')
                    ->required()
                    ->reactive(),

                Forms\Components\TextInput::make('tipo_cambio')
                    ->label('Tipo de Cambio')
                    ->regex('/^\d+(\.\d{1,4})?$/')
                    ->numeric()
                    ->default(36.6243)
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
            $montoInteres = ($monto * $interes * $plazo) / 100; // Calcular el monto de interés
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
                Tables\Columns\TextColumn::make('proveedor.nombrecompleto') // Nombre del proveedor
                    ->label('Proveedor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_desembolso') // Fecha de desembolso
                    ->label('Fecha de Desembolso')
                    ->date('d-m-yy')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto') // Monto del préstamo
                    ->label('Monto Desembolso C$')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                Tables\Columns\TextColumn::make('monto_interes') // Monto de interés
                    ->label('Monto de Interés C$')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                Tables\Columns\TextColumn::make('monto_total') // Monto total
                    ->label('Monto Total C$')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                Tables\Columns\TextColumn::make('interes') // Interés
                    ->label('Interés (%)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plazo_meses') // Plazo en meses
                    ->label('Plazo (meses)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_vencimiento') // Fecha de vencimiento
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
                Tables\Actions\EditAction::make(), // Acción para editar
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(), // Acción para eliminar en bloque
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
            'index' => Pages\ListPrestamos::route('/'),
            'create' => Pages\CreatePrestamo::route('/create'),
            'edit' => Pages\EditPrestamo::route('/{record}/edit'),
        ];
    }
}

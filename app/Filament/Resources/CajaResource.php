<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CajaResource\Pages;
use App\Models\Caja;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CajaResource extends Resource
{
    protected static ?string $model = Caja::class;
    protected static ?string $navigationGroup = 'Finanzas';
    protected static ?string $navigationLabel = 'Caja';
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('tipo'),
                TextColumn::make('monto')
                    ->money('NIO', locale: 'es_NI'),
                TextColumn::make('concepto'),
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->dateTime(),

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
            'index' => Pages\ListCajas::route('/'),
            'create' => Pages\CreateCaja::route('/create'),
            'edit' => Pages\EditCaja::route('/{record}/edit'),
        ];
    }
}

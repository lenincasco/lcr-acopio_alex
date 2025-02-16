<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombrecompleto')
                    ->required(),
                Forms\Components\Select::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'CLIENTE' => 'Cliente',
                        'CONSIGNATARIO' => 'Consignatario',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('cedula')
                    ->label('Cédula')
                    ->string()
                    ->regex('/^\d{3}-\d{6}-\d{4}[A-Z]$/')
                    ->mask('999-999999-9999a')
                    ->placeholder('XXX-XXXXXX-XXXXX')
                    ->extraInputAttributes([
                        'oninput' => "this.value = this.value.toUpperCase()",
                        'onpaste' => "this.value = this.value.toUpperCase()"
                    ])
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('dirección'),
                Forms\Components\TextInput::make('ciudad')
                    ->required(),
                Forms\Components\TextInput::make('municipio')
                    ->required(),
                Forms\Components\TextInput::make('celular')
                    ->required()
                    ->regex('/^[0-9]{8}$/')
                    ->mask('99999999')
                    ->placeholder('84152618')

                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombrecompleto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cedula')
                    ->searchable(),
                Tables\Columns\TextColumn::make('direccion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ciudad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('municipio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('celular')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}

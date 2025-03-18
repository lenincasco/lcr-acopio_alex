<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProveedorResource\Pages;
use App\Filament\Resources\ProveedorResource\RelationManagers;
use App\Models\Proveedor;
use App\Traits\RegularPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProveedorResource extends Resource
{
    use RegularPermissions;
    protected static ?string $model = Proveedor::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Cambia el nombre del menú
    public static function getNavigationLabel(): string
    {
        return 'Proveedores de Café'; // Aquí el texto que quieres que aparezca en el menú
    }

    // Cambia el título singular
    public static function getModelLabel(): string
    {
        return 'Proveedor'; // Singular
    }

    // Cambia el título plural
    public static function getPluralModelLabel(): string
    {
        return 'Proveedores'; // Plural
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombrecompleto')
                    ->required()
                    ->label('Nombre completo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('cedula')
                    ->label('Cédula')
                    ->required()
                    ->string()
                    ->regex('/^\d{3}-\d{6}-\d{4}[A-Z]$/') // Letra obligatoria
                    ->mask('999-999999-9999a')
                    ->placeholder('XXX-XXXXXX-XXXXX')
                    ->extraInputAttributes([
                        'oninput' => "this.value = this.value.toUpperCase()",
                        'onpaste' => "this.value = this.value.toUpperCase()"
                    ])
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('direccion')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ciudad')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('municipio')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('celular')
                    ->required()
                    ->regex('/^[0-9]{8}$/')
                    ->mask('99999999')
                    ->placeholder('8415-2618')
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
            'index' => Pages\ListProveedors::route('/'),
            'create' => Pages\CreateProveedor::route('/create'),
            'edit' => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}

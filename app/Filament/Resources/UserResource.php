<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'superadmin']);
    }
    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'superadmin']);
    }
    public static function canEdit($record): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'superadmin']);
    }
    public static function canDelete($record): bool
    {
        return auth()->user()->hasAnyRole(['superadmin']);
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->columnSpan(6)
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->columnSpan(6)
                            ->email()
                            ->unique(User::class, 'email')
                            ->required(),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->columnSpan(6)
                            ->password()
                            ->required()
                            ->confirmed(), // Esta opción requiere un campo 'password_confirmation'

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Contraseña')
                            ->columnSpan(6)
                            ->password()
                            ->required(),

                        Forms\Components\Select::make('roles')
                            ->label('Asigna un rol')
                            ->columnSpan(6)
                            ->multiple()
                            ->relationship('roles', 'name', function ($query) {
                                // Si el usuario logeado es 'admin', se excluyen los roles 'admin' y 'superadmin'
                                if (auth()->user()->hasRole('admin')) {
                                    $query->whereNotIn('name', ['admin', 'superadmin']);
                                }
                                return $query;
                            })
                            ->searchable()
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('roles')
                    ->getStateUsing(fn(\App\Models\User $record) => $record->getRoleNames()->implode(', '))
                    ->label('Roles'),

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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

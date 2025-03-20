<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
    public static function canEdit($record): bool
    {
        // Si el usuario a editar es super_admin y el usuario logueado no es super_admin, se bloquea la edición.
        if ($record->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
            return false;
        }
        // De lo contrario, se permite la edición si el usuario tiene alguno de los roles indicados.
        return auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
    public static function canDelete($record): bool
    {
        return auth()->user()->hasAnyRole(['super_admin']);
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
                            ->searchable()
                            ->options(function () {
                                // Obtenemos la consulta inicial de roles
                                $query = Role::query();
                                // Si el usuario logueado tiene rol 'admin', se excluyen 'admin' y 'super_admin'
                                if (auth()->user()->hasRole('admin')) {
                                    $query->whereNotIn('name', ['admin', 'super_admin']);
                                }
                                // Devuelve un arreglo donde la llave es el id del rol y el valor es su nombre
                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->relationship('roles', 'name', function ($query) {
                                // Si el usuario logeado es 'admin', se excluyen los roles 'admin' y 'super_admin'
                                if (auth()->user()->hasRole('admin')) {
                                    $query->whereNotIn('name', ['admin', 'super_admin']);
                                }
                                return $query;
                            })
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
                ])
                    ->visible(fn() => auth()->user()->hasRole('super_admin')),
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

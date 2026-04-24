<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Imię / nazwa')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            DateTimePicker::make('email_verified_at')
                ->label('Email zweryfikowany')
                ->native(false),

            Select::make('roles')
                ->label('Role')
                ->relationship('roles', 'name')
                ->options(Role::query()->pluck('name', 'id'))
                ->multiple()
                ->preload(),
        ]);
    }
}

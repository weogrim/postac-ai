<?php

declare(strict_types=1);

namespace App\Filament\Resources\Characters\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CharacterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nazwa')
                ->required()
                ->maxLength(120),

            Select::make('user_id')
                ->label('Autor')
                ->relationship('author', 'email')
                ->searchable()
                ->preload()
                ->getOptionLabelFromRecordUsing(fn (User $record): string => "{$record->name} ({$record->email})")
                ->required(),

            Textarea::make('prompt')
                ->label('Prompt systemowy')
                ->required()
                ->rows(10)
                ->maxLength(8000)
                ->columnSpanFull(),
        ]);
    }
}

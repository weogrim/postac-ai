<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nazwa')
                ->required()
                ->maxLength(64),

            Select::make('type')
                ->label('Typ')
                ->options([
                    'category' => 'Kategoria',
                    'tag' => 'Tag',
                ])
                ->required()
                ->native(false)
                ->default('tag'),

            TextInput::make('order_column')
                ->label('Kolejność')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->helperText('Niższa wartość = wyżej na liście (0 = pierwsza).'),
        ]);
    }
}

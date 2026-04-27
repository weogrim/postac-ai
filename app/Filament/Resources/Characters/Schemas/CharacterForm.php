<?php

declare(strict_types=1);

namespace App\Filament\Resources\Characters\Schemas;

use App\Character\Enums\CharacterKind;
use App\User\Models\UserModel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Spatie\Tags\Tag;

class CharacterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nazwa')
                ->required()
                ->maxLength(120),

            TextInput::make('slug')
                ->label('Slug (URL)')
                ->maxLength(160)
                ->regex('/^[a-z0-9-]+$/')
                ->validationMessages(['regex' => 'Tylko małe litery, cyfry i myślniki.'])
                ->unique(ignoreRecord: true)
                ->helperText('Auto-generowany z nazwy. Edytuj na własną odpowiedzialność — zmiana łamie istniejące linki.'),

            Select::make('user_id')
                ->label('Autor')
                ->relationship('author', 'email')
                ->searchable()
                ->preload()
                ->getOptionLabelFromRecordUsing(fn (UserModel $record): string => "{$record->name} ({$record->email})")
                ->required(),

            Select::make('kind')
                ->label('Typ')
                ->options(CharacterKind::class)
                ->required()
                ->default(CharacterKind::Regular)
                ->native(false),

            Toggle::make('is_official')
                ->label('Oficjalna')
                ->helperText('Postać widoczna jako oficjalna na stronie głównej (autor ukryty w UI).'),

            Textarea::make('description')
                ->label('Krótki opis')
                ->maxLength(500)
                ->rows(2)
                ->helperText('Widoczny w karcie i na profilu postaci.')
                ->columnSpanFull(),

            Textarea::make('greeting')
                ->label('Powitanie')
                ->maxLength(500)
                ->rows(3)
                ->helperText('Pierwsza wiadomość AI w nowej rozmowie.')
                ->columnSpanFull(),

            Textarea::make('prompt')
                ->label('Prompt systemowy')
                ->required()
                ->rows(10)
                ->maxLength(8000)
                ->columnSpanFull(),

            Select::make('categories')
                ->label('Kategorie (max 3)')
                ->multiple()
                ->maxItems(3)
                ->relationship(name: 'categories', titleAttribute: 'name')
                ->getOptionLabelFromRecordUsing(fn (Tag $record): string => $record->name ?? '')
                ->preload(),

            Select::make('freeTags')
                ->label('Tagi')
                ->multiple()
                ->relationship(name: 'freeTags', titleAttribute: 'name')
                ->getOptionLabelFromRecordUsing(fn (Tag $record): string => $record->name ?? '')
                ->preload(),
        ]);
    }
}

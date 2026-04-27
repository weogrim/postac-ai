<?php

declare(strict_types=1);

namespace App\Filament\Resources\DatingProfiles\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DatingProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Postać')
                ->description('Pola udostępnione przez postać AI (kind=dating, oficjalna).')
                ->schema([
                    TextInput::make('character_name')
                        ->label('Imię')
                        ->required()
                        ->maxLength(80),

                    Textarea::make('character_description')
                        ->label('Krótki opis')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Textarea::make('character_greeting')
                        ->label('Powitanie')
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Pierwsza wiadomość AI w nowej rozmowie.')
                        ->columnSpanFull(),

                    Textarea::make('character_prompt')
                        ->label('Prompt systemowy')
                        ->required()
                        ->rows(8)
                        ->maxLength(8000)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Profil randkowy')
                ->schema([
                    TextInput::make('age')
                        ->label('Wiek')
                        ->numeric()
                        ->required()
                        ->minValue(18)
                        ->maxValue(99),

                    TextInput::make('city')
                        ->label('Miasto')
                        ->required()
                        ->maxLength(64),

                    Textarea::make('bio')
                        ->label('Bio')
                        ->required()
                        ->rows(4)
                        ->maxLength(500)
                        ->columnSpanFull(),

                    TagsInput::make('interests')
                        ->label('Zainteresowania')
                        ->placeholder('Dodaj…')
                        ->columnSpanFull(),

                    ColorPicker::make('accent_color')
                        ->label('Kolor akcentu')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}

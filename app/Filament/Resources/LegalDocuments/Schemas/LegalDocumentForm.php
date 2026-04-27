<?php

declare(strict_types=1);

namespace App\Filament\Resources\LegalDocuments\Schemas;

use App\Legal\Enums\DocumentSlug;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LegalDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('slug')
                ->label('Typ dokumentu')
                ->options(DocumentSlug::class)
                ->required()
                ->native(false),

            TextInput::make('version')
                ->label('Wersja')
                ->numeric()
                ->minValue(1)
                ->required()
                ->default(1)
                ->helperText('Każda zmiana treści powinna mieć nowy numer wersji. Stare wersje zostają jako historia zgód.'),

            TextInput::make('title')
                ->label('Tytuł')
                ->required()
                ->maxLength(160),

            MarkdownEditor::make('content')
                ->label('Treść (Markdown)')
                ->required()
                ->columnSpanFull(),

            DateTimePicker::make('published_at')
                ->label('Publikacja')
                ->seconds(false)
                ->helperText('Pusty = wersja robocza, niewidoczna publicznie.'),
        ]);
    }
}

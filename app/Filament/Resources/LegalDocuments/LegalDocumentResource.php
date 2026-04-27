<?php

declare(strict_types=1);

namespace App\Filament\Resources\LegalDocuments;

use App\Filament\Resources\LegalDocuments\Pages\CreateLegalDocument;
use App\Filament\Resources\LegalDocuments\Pages\EditLegalDocument;
use App\Filament\Resources\LegalDocuments\Pages\ListLegalDocuments;
use App\Filament\Resources\LegalDocuments\Schemas\LegalDocumentForm;
use App\Filament\Resources\LegalDocuments\Tables\LegalDocumentsTable;
use App\Legal\Models\LegalDocumentModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LegalDocumentResource extends Resource
{
    protected static ?string $model = LegalDocumentModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Dokumenty prawne';

    protected static ?string $modelLabel = 'Dokument';

    protected static ?string $pluralModelLabel = 'Dokumenty prawne';

    protected static ?int $navigationSort = 80;

    public static function form(Schema $schema): Schema
    {
        return LegalDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalDocumentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegalDocuments::route('/'),
            'create' => CreateLegalDocument::route('/create'),
            'edit' => EditLegalDocument::route('/{record}/edit'),
        ];
    }
}

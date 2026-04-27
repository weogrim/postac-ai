<?php

declare(strict_types=1);

namespace App\Filament\Resources\DatingProfiles;

use App\Dating\Models\DatingProfileModel;
use App\Filament\Resources\DatingProfiles\Pages\CreateDatingProfile;
use App\Filament\Resources\DatingProfiles\Pages\EditDatingProfile;
use App\Filament\Resources\DatingProfiles\Pages\ListDatingProfiles;
use App\Filament\Resources\DatingProfiles\Schemas\DatingProfileForm;
use App\Filament\Resources\DatingProfiles\Tables\DatingProfilesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DatingProfileResource extends Resource
{
    protected static ?string $model = DatingProfileModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $navigationLabel = 'Randki';

    protected static ?string $modelLabel = 'Profil randkowy';

    protected static ?string $pluralModelLabel = 'Profile randkowe';

    protected static ?int $navigationSort = 25;

    public static function form(Schema $schema): Schema
    {
        return DatingProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DatingProfilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDatingProfiles::route('/'),
            'create' => CreateDatingProfile::route('/create'),
            'edit' => EditDatingProfile::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages\CreateProfile;
use App\Filament\Resources\ProfileResource\Pages\EditProfile;
use App\Filament\Resources\ProfileResource\Pages\ListProfiles;
use App\Filament\Resources\ProfileResource\Schemas\ProfileForm;
use App\Filament\Resources\ProfileResource\Tables\ProfilesTable;
use App\Models\Profile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProfileResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Profils';

    protected static ?string $modelLabel = 'Profil';

    protected static ?string $pluralModelLabel = 'Profils';

    public static function form(Schema $schema): Schema
    {
        return ProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProfilesTable::configure($table);
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
            'index' => ListProfiles::route('/'),
            'create' => CreateProfile::route('/create'),
            'edit' => EditProfile::route('/{record}/edit'),
        ];
    }
}

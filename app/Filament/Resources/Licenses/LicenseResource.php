<?php

namespace App\Filament\Resources\Licenses;

use App\Filament\Resources\Licenses\Pages\ManageLicenses;
use App\Filament\Resources\ResourceItems\ResourceItemResource;
use Illuminate\Database\Eloquent\Builder;

class LicenseResource extends ResourceItemResource
{
    protected static ?string $navigationLabel = 'Licenses';

    protected static ?string $pluralModelLabel = 'Licenses';

    protected static ?string $modelLabel = 'license';

    protected static ?string $resourceKind = 'license';

    protected static bool $standalone = true;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', 'license')->whereNull('game_id');
    }

    public static function getPages(): array
    {
        return ['index' => ManageLicenses::route('/')];
    }
}

<?php

namespace App\Filament\Resources\PayoutTypes;

use App\Filament\Resources\CatalogOptions\CatalogOptionResource;
use App\Filament\Resources\PayoutTypes\Pages\ManagePayoutTypes;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PayoutTypeResource extends CatalogOptionResource
{
    protected static ?string $navigationLabel = 'Payout Types';

    protected static ?string $pluralModelLabel = 'Payout Types';

    protected static ?string $modelLabel = 'payout type';

    protected static string|UnitEnum|null $navigationGroup = 'Taxonomy';

    protected static ?string $optionKind = 'payout_type';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', 'payout_type');
    }

    public static function getPages(): array
    {
        return ['index' => ManagePayoutTypes::route('/')];
    }
}

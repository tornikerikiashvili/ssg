<?php

namespace App\Filament\Resources\Regions;

use App\Filament\Resources\Regions\Pages\ManageRegions;
use App\Models\Region;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Access management';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('country_code')
                ->label('Country')
                ->options(config('map-countries'))
                ->searchable()
                ->required()
                ->in(array_keys(config('map-countries')))
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('country_code')->label('Country code')->placeholder('Not mapped')->searchable()->sortable(),
        ])->recordActions([EditAction::make()])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => ManageRegions::route('/')];
    }
}

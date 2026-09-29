<?php

namespace App\Filament\Resources\CatalogOptions;

use App\Filament\Resources\CatalogOptions\Pages\ManageCatalogOptions;
use App\Models\CatalogOption;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;

class CatalogOptionResource extends Resource
{
    protected static ?string $model = CatalogOption::class;

    protected static ?string $navigationLabel = 'Categories & filter options';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kind')->options(CatalogOption::KINDS)->required()->live()->disabledOn('edit'),
            TextInput::make('name')->required()->maxLength(100)->rules(fn ($get, $record) => [Rule::unique('catalog_options', 'name')->where('kind', $record?->kind ?? $get('kind'))->ignore($record?->id)]),
            TextInput::make('sort_order')->integer()->minValue(0)->default(0)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('kind')->formatStateUsing(fn ($state) => CatalogOption::KINDS[$state] ?? $state), TextColumn::make('sort_order')->sortable()])
            ->filters([SelectFilter::make('kind')->options(CatalogOption::KINDS)])->recordActions([EditAction::make()])->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return ['index' => ManageCatalogOptions::route('/')];
    }
}

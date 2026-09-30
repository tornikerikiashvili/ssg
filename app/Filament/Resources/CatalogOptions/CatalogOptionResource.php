<?php

namespace App\Filament\Resources\CatalogOptions;

use App\Models\CatalogOption;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;

abstract class CatalogOptionResource extends Resource
{
    protected static ?string $model = CatalogOption::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Taxonomy';

    protected static ?string $optionKind = null;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('kind')->default(static::$optionKind)->required()->rules([Rule::in([static::$optionKind])]),
            TextInput::make('name')->required()->maxLength(100)->rules(fn ($get, $record) => [Rule::unique('catalog_options', 'name')->where('kind', $record?->kind ?? $get('kind'))->ignore($record?->id)]),
            TextInput::make('sort_order')->integer()->minValue(0)->default(0)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('sort_order')->sortable()])
            ->recordActions([EditAction::make()])->defaultSort('sort_order');
    }
}

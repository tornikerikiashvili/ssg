<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\ManageCompanies;
use App\Models\Company;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Access management';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            Select::make('regions')->relationship('regions', 'name')->multiple()->searchable()->preload()
                ->helperText('Regional permissions inherited by company users. With no regions, only games without regional restrictions are accessible.'),
            Select::make('purchasedGames')->label('Bought games')->relationship('purchasedGames', 'title', modifyQueryUsing: fn (Builder $query): Builder => $query->select(['games.id', 'games.title']))
                ->multiple()->searchable()->preload()->helperText('Select the games purchased by this company.'),
            Toggle::make('is_active')->default(true)->helperText('Disabling a company revokes access for all of its partner users.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('users_count')->counts('users')->label('Users'),
            IconColumn::make('is_active')->boolean(),
            TextColumn::make('created_at')->date(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCompanies::route('/')];
    }
}

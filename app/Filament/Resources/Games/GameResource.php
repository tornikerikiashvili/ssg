<?php

namespace App\Filament\Resources\Games;

use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Filament\Resources\Games\Pages\ManageGames;
use App\Filament\Resources\Games\RelationManagers\ResourcesRelationManager;
use App\Models\CatalogOption;
use App\Models\Game;
use App\Models\GameRegion;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationLabel = 'Games';

    protected static ?string $pluralModelLabel = 'Games';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    public static function form(Schema $schema): Schema
    {
        $option = fn (string $field, string $kind, string $label) => Select::make($field)->label($label)
            ->options(fn () => CatalogOption::options($kind))->searchable()
            ->rules([Rule::exists('catalog_options', 'id')->where('kind', $kind)]);

        return $schema->components([Tabs::make('Game editor')->columnSpanFull()->tabs([
            Tab::make('Details')->schema([
                Select::make('release_status')->options(['upcoming' => 'Upcoming', 'released' => 'Released'])->required()->default('released'),
                Toggle::make('preview_enabled')->label('Allow upcoming game preview')->helperText('Published upcoming games can open for permitted users. Assets stay unavailable until released.'),
                TextInput::make('title')->required()->maxLength(255),
                TextInput::make('slug')->required()->maxLength(255)->alphaDash()->unique(ignoreRecord: true),
                Textarea::make('description')->rows(4)->maxLength(20000)->columnSpanFull(),
                $option('category_id', 'category', 'Category')->required(),
                $option('game_type_id', 'game_type', 'Game type'),
                $option('payout_type_id', 'payout_type', 'Payout type'),
                $option('volatility_id', 'volatility', 'Volatility'),
                TextInput::make('rtp')->label('RTP (%)')->numeric()->minValue(0)->maxValue(100)->step(0.01),
                DatePicker::make('release_date'),
                Select::make('cover_image')->options(Game::COVERS)->label('Reference artwork'),
                Toggle::make('is_featured'),
            ])->columns(2),
            Tab::make('Features, specifications & rules')->schema([
                Textarea::make('features')->rows(6)->maxLength(30000),
                TagsInput::make('feature_tags')->nestedRecursiveRules(['string', 'max:100']),
                KeyValue::make('specifications')->keyLabel('Specification')->valueLabel('Value'),
                Textarea::make('rules')->rows(8)->maxLength(30000),
            ]),
            Tab::make('Regions & engagement tools')->schema([
                Repeater::make('regionAvailabilities')->label('Regional availability')->relationship()->schema([
                    Select::make('region_id')->label('Region')->relationship('region', 'name')->searchable()->preload()->required()->distinct()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                    Select::make('status')->options(GameRegion::STATUSES)->required()->default('available'),
                ])->columns(2)->maxItems(250)->defaultItems(0)->helperText('No regions means available in all regions. Available and Limited regions grant access when the partner has a matching regional permission.'),
                Select::make('engagementTools')->relationship('engagementTools', 'title')->multiple()->searchable()->preload(),
            ]),
            Tab::make('Publishing & access')->schema([
                Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->label('Audience company')->placeholder('All partner companies'),
                Toggle::make('is_published')->label('Published to client area'),
                Toggle::make('is_demo')->label('Sample / demo content'),
            ]),
        ])]);
    }

    public static function getRelations(): array
    {
        return [ResourcesRelationManager::class];
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable()->limit(45),
            TextColumn::make('category')->badge(),
            TextColumn::make('company.name')->label('Audience')->placeholder('All partners'),
            IconColumn::make('is_published')->label('Published')->boolean(),
            IconColumn::make('is_demo')->label('Demo')->boolean(),
            TextColumn::make('updated_at')->since()->sortable(),
        ])->filters([
            TernaryFilter::make('is_published'),
            SelectFilter::make('company_id')->relationship('company', 'name')->label('Company'),
            SelectFilter::make('category_id')->label('Category')->options(fn () => CatalogOption::options('category')),
        ])->recordActions([EditAction::make()->url(fn (Game $record) => static::getUrl('edit', ['record' => $record])), DeleteAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageGames::route('/'), 'create' => CreateGame::route('/create'), 'edit' => EditGame::route('/{record}/edit')];
    }
}

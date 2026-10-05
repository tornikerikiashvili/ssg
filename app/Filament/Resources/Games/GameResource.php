<?php

namespace App\Filament\Resources\Games;

use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Filament\Resources\Games\Pages\ManageGames;
use App\Filament\Resources\Games\RelationManagers\ResourcesRelationManager;
use App\Models\CatalogOption;
use App\Models\Game;
use App\Models\GameRegion;
use App\SyncDropboxGame;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use RuntimeException;
use UnitEnum;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationLabel = 'Games';

    protected static ?string $pluralModelLabel = 'Games';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $option = fn (string $field, string $kind, string $label) => Select::make($field)->label($label)
            ->options(fn () => CatalogOption::options($kind))->searchable()
            ->rules([Rule::exists('catalog_options', 'id')->where('kind', $kind)]);

        return $schema->components([Grid::make(['default' => 1, 'lg' => 10])->columnSpanFull()->schema([
            Tabs::make('Game editor')->tabs([
                Tab::make('Details')->schema([
                    TextInput::make('title')->required()->maxLength(255),
                    TextInput::make('slug')->required()->maxLength(255)->alphaDash()->unique(ignoreRecord: true),
                    Textarea::make('description')->rows(4)->maxLength(20000)->columnSpanFull(),
                    Section::make('Play Demo')->schema([
                        TextInput::make('demo_url')->label('Game link')->url()->rules(['url:http,https'])->maxLength(2048)
                            ->placeholder('https://example.com/game')->helperText('Opens in a new tab. Leave empty to hide the Play Demo button.'),
                    ])->columnSpanFull(),
                    SpatieMediaLibraryFileUpload::make('cover')->label('Cover image')->columnSpanFull()
                        ->collection('cover')->disk('public')->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)
                        ->helperText('JPG, PNG or WebP, up to 5 MB. Stored on this website, independently of Dropbox.'),
                ])->columns(2),
                Tab::make('Features, specifications & rules')->schema([
                    Textarea::make('features')->rows(6)->maxLength(30000),
                    TagsInput::make('feature_tags')->splitKeys(['Enter', ','])->helperText('Press Enter or type a comma after each tag.')->nestedRecursiveRules(['string', 'max:100']),
                    Repeater::make('specifications')->schema([
                        TextInput::make('label')->label('Heading')->required()->maxLength(2300),
                        Repeater::make('children')->label('Child points')
                            ->simple(TextInput::make('text')->required()->maxLength(2000))
                            ->defaultItems(0)->maxItems(50)->addActionLabel('Add child point'),
                    ])->defaultItems(0)->maxItems(100)->addActionLabel('Add specification')
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                        ->helperText('Write the complete line in Heading and optionally add child points.'),
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
                Tab::make('Dropbox assets')->schema([
                    TextInput::make('dropbox_folder_path')->label('Dropbox folder path')->maxLength(2000)
                        ->regex('~^/(?!Users/|home/)(?!.*(?:^|/)\.{1,2}(?:/|$))[^\r\n]+$~')
                        ->placeholder('/Games/Game1/assets')
                        ->helperText('Use a Dropbox path, not a local computer path or sharing URL. Save to sync. Subfolders become asset categories; changes sync every five minutes.'),
                    Placeholder::make('dropbox_sync_status')->label('Last successful sync')
                        ->content(fn (?Game $record): string => $record?->dropbox_synced_at?->format('Y-m-d H:i:s') ?? 'Not synced'),
                    Placeholder::make('dropbox_asset_count')->label('Dropbox assets')
                        ->content(fn (?Game $record): string => (string) ($record?->resources()->whereNotNull('dropbox_file_id')->where('dropbox_available', true)->count() ?? 0)),
                    Placeholder::make('dropbox_error')->label('Sync error')
                        ->content(fn (?Game $record): string => $record?->dropbox_sync_error ?? 'None'),
                ]),
            ])->columnSpan(['default' => 1, 'lg' => 7]),
            Group::make([
                Section::make('Publishing & access')->schema([
                    Select::make('release_status')->options(['upcoming' => 'Upcoming', 'released' => 'Released'])->required()->default('released'),
                    Toggle::make('preview_enabled')->label('Allow upcoming game preview')->helperText('Published upcoming games can open for permitted users. Assets stay unavailable until released.'),
                    Toggle::make('is_featured'),
                    Toggle::make('is_published')->label('Published to client area'),
                ]),
                Section::make('Game information')->schema([
                    $option('category_id', 'category', 'Category')->required(),
                    $option('game_type_id', 'game_type', 'Game type'),
                    $option('payout_type_id', 'payout_type', 'Payout type'),
                    $option('volatility_id', 'volatility', 'Volatility'),
                    TextInput::make('rtp')->label('RTP (%)')->numeric()->minValue(0)->maxValue(100)->step(0.01),
                    TextInput::make('min_bet')->label('Min bet')->numeric()->minValue(0)->maxValue(9999999999.99)->step(0.01),
                    TextInput::make('max_bet')->label('Max bet')->numeric()->minValue(0)->maxValue(9999999999.99)->step(0.01)
                        ->rules(fn ($get): array => filled($get('min_bet')) ? ['gte:data.min_bet'] : []),
                    TextInput::make('languages')->label('Languages')->maxLength(255)->helperText('Enter a count or supported languages, for example 22 or English, Georgian.'),
                    TextInput::make('certifications')->label('Certifications')->maxLength(255)->placeholder('GLI, BMM, MGA'),
                    DatePicker::make('release_date'),
                ]),
            ])->columnSpan(['default' => 1, 'lg' => 3]),
        ])]);
    }

    public static function getRelations(): array
    {
        return [ResourcesRelationManager::class];
    }

    public static function syncDropbox(Game $game): void
    {
        if (! $game->dropbox_folder_path) {
            return;
        }
        try {
            $count = app(SyncDropboxGame::class)->sync($game);
            Notification::make()->title('Dropbox synced')->body($count.' assets available.')->success()->send();
        } catch (RuntimeException $exception) {
            Notification::make()->title('Dropbox sync failed')->body($exception->getMessage())->danger()->send();
        }
        $game->refresh();
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable()->limit(45),
            TextColumn::make('category')->badge(),
            IconColumn::make('is_published')->label('Published')->boolean(),
            TextColumn::make('updated_at')->since()->sortable(),
        ])->filters([
            TernaryFilter::make('is_published'),
            SelectFilter::make('category_id')->label('Category')->options(fn () => CatalogOption::options('category')),
        ])->recordActions([EditAction::make()->url(fn (Game $record) => static::getUrl('edit', ['record' => $record])), DeleteAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageGames::route('/'), 'create' => CreateGame::route('/create'), 'edit' => EditGame::route('/{record}/edit')];
    }
}

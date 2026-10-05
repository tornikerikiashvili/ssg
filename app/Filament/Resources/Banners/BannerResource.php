<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Models\Banner;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        $image = fn (string $field, string $label) => FileUpload::make($field)->label($label)->disk('public')->directory('banners')->image()->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])->maxSize(5120)->preventFilePathTampering();
        $button = fn (string $prefix, string $label) => Section::make($label)->schema([
            TextInput::make($prefix.'_label')->label('Button text')->maxLength(60),
            Select::make($prefix.'_target')->label('Destination')->options(Banner::TARGETS)->required()->default('none'),
            TextInput::make($prefix.'_url')->label('Website URL')->url()->regex('~^https?://~i')->maxLength(2048)->requiredIf($prefix.'_target', 'url')->helperText('Used only for Website URL. Game destinations use the linked game.'),
        ])->columns(2);

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 10])->columnSpanFull()->schema([
                Group::make([
                    Section::make('Content')->schema([
                        TextInput::make('title')->label('Headline')->required()->maxLength(255),
                        TextInput::make('label')->label('Top label')->placeholder('Featured Game / Coming Next')->maxLength(100),
                        TextInput::make('subtitle')->maxLength(255)->columnSpanFull(),
                    ])->columns(2),
                    Section::make('Media')->schema([
                        $image('logo_path', 'Logo (SVG or transparent PNG recommended)')->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/jpeg', 'image/webp']),
                        $image('image_path', 'Foreground artwork'),
                        FileUpload::make('video_path')->label('Background video')->disk('public')->directory('banners')->acceptedFileTypes(['video/mp4', 'video/webm'])->maxSize(12288)->preventFilePathTampering(),
                        $image('poster_path', 'Video poster'),
                    ])->columns(2),
                    $button('primary', 'Primary button'),
                    Section::make('Play Game button')->schema([
                        TextInput::make('secondary_url')->label('Test play URL')->url()->regex('~^https?://~i')->maxLength(2048)
                            ->helperText('Opens the test-play version of the game. Leave empty to hide the Play Game button.'),
                    ]),
                ])->columnSpan(['default' => 1, 'lg' => 7]),
                Group::make([
                    Section::make('Publishing & placement')->schema([
                        Toggle::make('is_published')->label('Published'),
                        Select::make('pages')->options(Banner::PAGES)->multiple()->required()->minItems(1),
                        TextInput::make('sort_order')->integer()->minValue(0)->required()->default(0),
                    ]),
                    Section::make('Game & appearance')->schema([
                        Select::make('game_id')->relationship('game', 'title')->searchable()->preload()->helperText('Optional. A linked banner is shown only when the user can access that game.'),
                        Toggle::make('show_game_stats')->label('Show game RTP and volatility')->default(true),
                        Select::make('variant')->options(['standard' => 'Original standard', 'blue' => 'Original blue'])->required()->default('standard'),
                        Select::make('original_artwork')->options(Banner::ARTWORK)->label('Use original HTML artwork')->helperText('Used when no image or video is uploaded.'),
                        Toggle::make('use_original_video')->label('Use original HTML background video'),
                    ]),
                ])->columnSpan(['default' => 1, 'lg' => 3]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->limit(45),
            TextColumn::make('pages')->badge(),
            TextColumn::make('game.title')->label('Game'),
            TextColumn::make('sort_order')->sortable(),
            IconColumn::make('is_published')->boolean(),
        ])->filters([TernaryFilter::make('is_published')])
            ->recordActions([EditAction::make(), DeleteAction::make()])->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }
}

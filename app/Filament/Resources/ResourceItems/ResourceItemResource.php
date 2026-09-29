<?php

namespace App\Filament\Resources\ResourceItems;

use App\Filament\Resources\ResourceItems\Pages\ManageResourceItems;
use App\Models\CatalogOption;
use App\Models\ResourceItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;

class ResourceItemResource extends Resource
{
    protected static ?string $model = ResourceItem::class;

    protected static ?string $navigationLabel = 'Resource library';

    protected static ?string $pluralModelLabel = 'Resource library';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255)->alphaDash()->unique(ignoreRecord: true),
            Textarea::make('description')->rows(5)->maxLength(20000)->columnSpanFull(),

            Select::make('kind')->options(ResourceItem::KINDS)->required()->default('download')->live(),
            Select::make('game_id')->hidden(fn ($livewire) => $livewire instanceof RelationManager)->dehydrated(fn ($livewire) => ! ($livewire instanceof RelationManager))->relationship('game', 'title')->searchable()->preload()->helperText('Linked resources also inherit the game’s visibility.'),
            Select::make('catalog_option_id')->label('Asset category / document type')->options(fn ($get) => CatalogOption::options($get('kind') === 'documentation' ? 'document' : 'asset'))
                ->rules(fn ($get) => [Rule::exists('catalog_options', 'id')->where('kind', $get('kind') === 'documentation' ? 'document' : 'asset')])->searchable(),
            FileUpload::make('file_path')->label('File')->disk('local')->directory('game-assets')->visibility('private')->maxSize(12288)
                ->preventFilePathTampering()->helperText('Private local storage, up to 12 MB per file. Existing demo files remain available; uploading replaces the file association.'),

            Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->label('Audience company')->placeholder('All partner companies')->helperText('Leave empty to share with all active partner companies.'),
            Toggle::make('is_published')->label('Published to client area'),
            Toggle::make('is_demo')->label('Sample / demo content'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable()->limit(45),
            TextColumn::make('kind')->badge(), TextColumn::make('game.title')->placeholder('General'),
            TextColumn::make('company.name')->label('Audience')->placeholder('All partners'),
            IconColumn::make('is_published')->label('Published')->boolean(),
            IconColumn::make('is_demo')->label('Demo')->boolean(),
            TextColumn::make('updated_at')->since()->sortable(),
        ])->filters([
            TernaryFilter::make('is_published'),
            SelectFilter::make('company_id')->relationship('company', 'name')->label('Company'),
            SelectFilter::make('kind')->options(ResourceItem::KINDS),
        ])->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageResourceItems::route('/')];
    }
}

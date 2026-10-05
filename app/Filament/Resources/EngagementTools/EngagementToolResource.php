<?php

namespace App\Filament\Resources\EngagementTools;

use App\Filament\Resources\EngagementTools\Pages\ManageEngagementTools;
use App\Models\EngagementTool;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class EngagementToolResource extends Resource
{
    protected static ?string $model = EngagementTool::class;

    protected static ?string $navigationLabel = 'Engagement tools';

    protected static ?string $pluralModelLabel = 'Engagement tools';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255)->alphaDash()->unique(ignoreRecord: true),
            Textarea::make('description')->rows(5)->maxLength(20000)->columnSpanFull(),

            FileUpload::make('cover_image')->label('Cover image')->image()->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])->disk('public')->directory('engagement-tools')->maxSize(5120)->preventFilePathTampering()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable()->limit(45),
            ImageColumn::make('cover_image')->label('Cover')->disk('public'),
            TextColumn::make('updated_at')->since()->sortable(),
        ])->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageEngagementTools::route('/')];
    }
}

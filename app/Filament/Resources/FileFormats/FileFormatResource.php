<?php

namespace App\Filament\Resources\FileFormats;

use App\Filament\Resources\FileFormats\Pages\ManageFileFormats;
use App\Models\FileFormat;
use Filament\Actions\EditAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FileFormatResource extends Resource
{
    protected static ?string $model = FileFormat::class;

    protected static ?string $recordTitleAttribute = 'extension';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('extension')->disabled()->helperText('Detected automatically during Dropbox sync.'),
            SpatieMediaLibraryFileUpload::make('icon')->collection('icon')->disk('public')->image()
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])->maxSize(2048)
                ->helperText('SVG, PNG, WebP or JPG, up to 2 MB. Used when an image thumbnail is unavailable.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            SpatieMediaLibraryImageColumn::make('icon')->collection('icon')->label('Fallback icon'),
            TextColumn::make('extension')->searchable()->sortable()->formatStateUsing(fn (string $state): string => mb_strtoupper($state)),
            TextColumn::make('created_at')->label('Discovered')->since(),
        ])->recordActions([EditAction::make()])->defaultSort('extension');
    }

    public static function getPages(): array
    {
        return ['index' => ManageFileFormats::route('/')];
    }
}

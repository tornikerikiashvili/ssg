<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Models\Announcement;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?string $pluralModelLabel = 'Announcements';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255)->alphaDash()->unique(ignoreRecord: true),
            Textarea::make('description')->rows(5)->maxLength(20000)->columnSpanFull(),

            Toggle::make('show_on_dashboard')->label('Show on Dashboard')->default(true),
            Toggle::make('show_on_roadmap')->label('Show on Roadmap')->default(false),
            Select::make('priority')->options(['info' => 'Information', 'important' => 'Important'])->required()->default('info'),

            Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->label('Audience company')->placeholder('All partner companies')->helperText('Leave empty to share with all active partner companies.'),
            Toggle::make('is_published')->label('Published to client area'),
            Toggle::make('is_demo')->label('Sample / demo content'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable()->limit(45),
            TextColumn::make('priority')->badge(),
            TextColumn::make('company.name')->label('Audience')->placeholder('All partners'),
            IconColumn::make('is_published')->label('Published')->boolean(),
            IconColumn::make('is_demo')->label('Demo')->boolean(),
            TextColumn::make('updated_at')->since()->sortable(),
        ])->filters([
            TernaryFilter::make('is_published'),
            SelectFilter::make('company_id')->relationship('company', 'name')->label('Company'),

        ])->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageAnnouncements::route('/')];
    }
}

<?php

namespace App\Filament\Resources\PortalNotifications;

use App\Filament\Resources\PortalNotifications\Pages\ManagePortalNotifications;
use App\Models\PortalNotification;
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

class PortalNotificationResource extends Resource
{
    protected static ?string $model = PortalNotification::class;

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $modelLabel = 'Notification';

    protected static ?string $slug = 'notifications';

    protected static ?string $pluralModelLabel = 'Notifications';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Communications';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('teaser')->maxLength(255)->helperText('Short summary shown between the title and description.'),
            Textarea::make('description')->rows(5)->maxLength(20000)->columnSpanFull(),
            Select::make('color')->label('Color')->options(PortalNotification::NOTIFICATION_COLORS)->required()->default('blue')->in(array_keys(PortalNotification::NOTIFICATION_COLORS)),

            Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->label('Audience company')->placeholder('All partner companies')->helperText('Leave empty to share with all active partner companies.'),
            Toggle::make('is_published')->label('Published to client area'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable()->limit(45),
            TextColumn::make('company.name')->label('Audience')->placeholder('All partners'),
            IconColumn::make('is_published')->label('Published')->boolean(),
            TextColumn::make('updated_at')->since()->sortable(),
        ])->filters([
            TernaryFilter::make('is_published'),
            SelectFilter::make('company_id')->relationship('company', 'name')->label('Company'),

        ])->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManagePortalNotifications::route('/')];
    }
}

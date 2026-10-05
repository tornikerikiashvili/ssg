<?php

namespace App\Filament\Resources\RoadmapItems;

use App\Filament\Resources\RoadmapItems\Pages\ManageRoadmapItems;
use App\Models\RoadmapItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class RoadmapItemResource extends Resource
{
    protected static ?string $model = RoadmapItem::class;

    protected static ?string $navigationLabel = 'Roadmap';

    protected static ?string $pluralModelLabel = 'Roadmap';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('game_id')->relationship('game', 'title')->searchable()->preload()->label('Linked game')->helperText('Select the existing Games record. Leave empty only for general plans.'),
            Select::make('milestone_type')->options(RoadmapItem::TYPES)->required()->default('initial_release'),
            Select::make('region_id')->relationship('region', 'name')->searchable()->preload()->label('Launch region')->helperText('Use for regional milestones; visibility is restricted to this region.'),
            TextInput::make('title')->label('Milestone title')->required()->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255)->alphaDash()->unique(ignoreRecord: true),
            Textarea::make('description')->rows(5)->maxLength(20000)->columnSpanFull(),

            Select::make('status')->options(RoadmapItem::STATUSES)->required()->default('planned'),
            DatePicker::make('target_date')->label('Target date'),
            TextInput::make('target_quarter')->label('Target quarter')->placeholder('2027 Q1')->regex('/^20[0-9]{2} Q[1-4]$/')->helperText('Optional, when the exact date is not known.'),
            TextInput::make('progress')->label('Development progress (%)')->numeric()->integer()->minValue(0)->maxValue(100)->helperText('Optional manual estimate. Leave empty to hide the progress bar.'),

            Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->label('Audience company')->placeholder('All partner companies')->helperText('Leave empty to share with all active partner companies.'),
            Toggle::make('is_published')->label('Published to client area'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable()->limit(45),
            TextColumn::make('game.title')->label('Game')->searchable(),
            TextColumn::make('milestone_type')->label('Milestone'),
            TextColumn::make('status')->badge(), TextColumn::make('target_date')->date()->sortable(),
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
        return ['index' => ManageRoadmapItems::route('/')];
    }
}

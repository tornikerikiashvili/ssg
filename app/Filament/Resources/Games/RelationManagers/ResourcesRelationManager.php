<?php

namespace App\Filament\Resources\Games\RelationManagers;

use App\Filament\Resources\Documentations\DocumentationResource;
use Filament\Actions\AssociateAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResourcesRelationManager extends RelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'resources';

    protected static ?string $title = 'Documentation';

    public function form(Schema $schema): Schema
    {
        return DocumentationResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return DocumentationResource::table($table)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('kind', 'documentation'))
            ->inverseRelationship('game')
            ->recordTitleAttribute('title')
            ->headerActions([
                AssociateAction::make()->label('Assign documentation')
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query->where('kind', 'documentation')->whereNull('game_id'))
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make()->label('Remove from game'),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Games\RelationManagers;

use App\Filament\Resources\ResourceItems\ResourceItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ResourcesRelationManager extends RelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'resources';

    protected static ?string $title = 'Game assets & documentation';

    public function form(Schema $schema): Schema
    {
        return ResourceItemResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return ResourceItemResource::table($table)->headerActions([CreateAction::make()]);
    }
}

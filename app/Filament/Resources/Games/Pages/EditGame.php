<?php

namespace App\Filament\Resources\Games\Pages;

use App\Filament\Resources\Games\GameResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditGame extends EditRecord
{
    protected static string $resource = GameResource::class;

    protected function afterSave(): void
    {
        GameResource::syncDropbox($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('syncDropbox')->label('Sync Dropbox now')
            ->visible(fn (): bool => filled($this->record->dropbox_folder_path))
            ->action(fn () => GameResource::syncDropbox($this->record))];
    }
}

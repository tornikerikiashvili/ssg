<?php

namespace App\Filament\Resources\PayoutTypes\Pages;

use App\Filament\Resources\PayoutTypes\PayoutTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePayoutTypes extends ManageRecords
{
    protected static string $resource = PayoutTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

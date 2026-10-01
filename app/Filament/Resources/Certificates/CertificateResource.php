<?php

namespace App\Filament\Resources\Certificates;

use App\Filament\Resources\Certificates\Pages\ManageCertificates;
use App\Filament\Resources\ResourceItems\ResourceItemResource;
use Illuminate\Database\Eloquent\Builder;

class CertificateResource extends ResourceItemResource
{
    protected static ?string $navigationLabel = 'Certifications';

    protected static ?string $pluralModelLabel = 'Certifications';

    protected static ?string $modelLabel = 'certification';

    protected static ?string $resourceKind = 'certificate';

    protected static bool $standalone = true;

    protected static bool $requiresSlug = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', 'certificate')->whereNull('game_id');
    }

    public static function getPages(): array
    {
        return ['index' => ManageCertificates::route('/')];
    }
}

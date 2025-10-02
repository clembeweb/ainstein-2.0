<?php

namespace App\Filament\Resources\ContentGenerationResource\Pages;

use App\Filament\Resources\ContentGenerationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContentGeneration extends ViewRecord
{
    protected static string $resource = ContentGenerationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
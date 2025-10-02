<?php

namespace App\Filament\Resources\ContentGenerationResource\Pages;

use App\Filament\Resources\ContentGenerationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentGeneration extends EditRecord
{
    protected static string $resource = ContentGenerationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
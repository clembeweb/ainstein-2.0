<?php

namespace App\Filament\Resources\ContentGenerationResource\Pages;

use App\Filament\Resources\ContentGenerationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContentGenerations extends ListRecords
{
    protected static string $resource = ContentGenerationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
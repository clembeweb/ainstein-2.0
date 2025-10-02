<?php

namespace App\Filament\Admin\Resources\Prompts\Pages;

use App\Filament\Admin\Resources\Prompts\PromptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrompts extends ListRecords
{
    protected static string $resource = PromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\Prompts\Pages;

use App\Filament\Admin\Resources\Prompts\PromptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrompt extends EditRecord
{
    protected static string $resource = PromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

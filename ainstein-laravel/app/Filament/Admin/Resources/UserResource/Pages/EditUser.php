<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle password update
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password_hash'] = bcrypt($data['password']);
            unset($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }
}

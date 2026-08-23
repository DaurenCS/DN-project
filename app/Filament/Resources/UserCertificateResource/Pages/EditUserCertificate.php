<?php

namespace App\Filament\Resources\UserCertificateResource\Pages;

use App\Filament\Resources\UserCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserCertificate extends EditRecord
{
    protected static string $resource = UserCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

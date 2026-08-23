<?php

namespace App\Filament\Resources\UserCertificateResource\Pages;

use App\Filament\Resources\UserCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserCertificates extends ListRecords
{
    protected static string $resource = UserCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

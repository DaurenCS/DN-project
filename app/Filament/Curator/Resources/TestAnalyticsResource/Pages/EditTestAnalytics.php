<?php

namespace App\Filament\Curator\Resources\TestAnalyticsResource\Pages;

use App\Filament\Curator\Resources\TestAnalyticsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestAnalytics extends EditRecord
{
    protected static string $resource = TestAnalyticsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

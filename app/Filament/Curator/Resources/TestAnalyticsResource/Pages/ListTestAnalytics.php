<?php

namespace App\Filament\Curator\Resources\TestAnalyticsResource\Pages;

use App\Filament\Curator\Resources\TestAnalyticsResource;
use Filament\Resources\Pages\ListRecords;

class ListTestAnalytics extends ListRecords
{
    protected static string $resource = TestAnalyticsResource::class;

    protected function getHeaderActions(): array
    {
        // Оставляем пустым, чтобы сверху не было кнопки "Создать"
        return [];
    }
}

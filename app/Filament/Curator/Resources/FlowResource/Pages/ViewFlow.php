<?php

namespace App\Filament\Curator\Resources\FlowResource\Pages;

use App\Filament\Curator\Resources\FlowResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewFlow extends ViewRecord
{
    protected static string $resource = FlowResource::class;

    // Устанавливаем заголовок страницы: "Департамент: Название"
    public function getTitle(): string|Htmlable
    {
        return 'Департамент: ' . $this->getRecord()->name;
    }

    // Убираем верхние кнопки (например, "Редактировать", "Удалить" и т.д.)
    protected function getHeaderActions(): array
    {
        return [];
    }
}

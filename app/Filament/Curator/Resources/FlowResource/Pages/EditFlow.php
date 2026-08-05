<?php

namespace App\Filament\Curator\Resources\FlowResource\Pages;

use App\Filament\Curator\Resources\FlowResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditFlow extends EditRecord
{
    protected static string $resource = FlowResource::class;

    // Настраиваем заголовок страницы: "Департамент: Название"
    public function getTitle(): string|Htmlable
    {
        return 'Департамент: ' . $this->getRecord()->name;
    }

    // Убираем подзаголовок (если был)
    public function getSubheading(): ?string
    {
        return null;
    }

    // Отключаем или очищаем верхние действия (например, кнопку "Удалить")
    protected function getHeaderActions(): array
    {
        return [];
    }
}

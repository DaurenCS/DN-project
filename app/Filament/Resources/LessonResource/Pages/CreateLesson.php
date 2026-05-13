<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    /**
     * Этот метод переопределяет URL для кнопки "Сохранить и создать еще",
     * сохраняя текущие параметры запроса (module_id).
     */
    protected function getCreateAnotherRecordUrl(): string
    {
        return static::getResource()::getUrl('create', [
            'module_id' => request()->query('module_id'),
        ]);
    }

    /**
     * Опционально: куда возвращаться после обычного сохранения.
     * Если хотите вернуться в редактирование модуля:
     */
    protected function getRedirectUrl(): string
    {
        if ($this->record->module_id) {
            return \App\Filament\Resources\ModuleResource::getUrl('edit', [
                'record' => $this->record->module_id
            ]);
        }

        return $this->getResource()::getUrl('index');
    }
}

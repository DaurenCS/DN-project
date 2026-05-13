<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

use App\Filament\Resources\LessonResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';
    protected static ?string $title = 'Уроки модуля';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Название'),
                Tables\Columns\TextColumn::make('sort_order')->label('Порядок'),
            ])
            ->headerActions([
                // Кнопка СОЗДАТЬ: перенаправляет на страницу создания урока
                // Передаем module_id, чтобы урок сразу привязался к текущему модулю
                Tables\Actions\CreateAction::make()
                    ->url(fn (): string => LessonResource::getUrl('create', [
                        'module_id' => $this->getOwnerRecord()->id,
                    ])),
            ])
            ->actions([
                // Кнопка РЕДАКТИРОВАТЬ: перенаправляет на страницу редактирования урока
                Tables\Actions\EditAction::make()
                    ->url(fn ($record): string => LessonResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->recordUrl(fn ($record): string => LessonResource::getUrl('edit', ['record' => $record]));
    }
}

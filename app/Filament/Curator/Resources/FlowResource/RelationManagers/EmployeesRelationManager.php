<?php

namespace App\Filament\Curator\Resources\FlowResource\RelationManagers;

use App\Models\Course;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    protected static ?string $title = 'Сотрудники';

    protected static ?string $modelLabel = 'Сотрудник';
    protected static ?string $pluralModelLabel = 'Сотрудники';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('ФИО')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('courses.name')
                    ->label('Назначенные курсы')
                    ->badge()
                    ->placeholder('Курсы не назначены')
                    ->color('primary')
                    ->getStateUsing(function ($record) {
                        $courseId = $this->getCourseFilterValue();

                        if ($courseId) {
                            return $record->courses->firstWhere('id', $courseId)?->name;
                        }

                        return $record->courses->pluck('name')->toArray();
                    }),

                Tables\Columns\TextColumn::make('course_status')
                    ->label('Статус курса')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'completed'   => 'success',
                        'in_progress' => 'warning',
                        default       => 'gray',
                    })
                    ->getStateUsing(function ($record) {
                        $courseId = $this->getCourseFilterValue();
                        if (!$courseId) {
                            return null;
                        }
                        return $record->courses
                            ->firstWhere('id', $courseId)
                            ?->pivot
                            ?->status;
                    })
                    ->visible(fn () => filled($this->getCourseFilterValue())),

                Tables\Columns\TextColumn::make('course_progress')
                    ->label('Прогресс')
                    ->getStateUsing(function ($record) {
                        $courseId = $this->getCourseFilterValue();
                        if (!$courseId) {
                            return null;
                        }
                        $progress = $record->courses
                            ->firstWhere('id', $courseId)
                            ?->pivot
                            ?->progress;

                        return $progress !== null ? $progress . '%' : '—';
                    })
                    ->visible(fn () => filled($this->getCourseFilterValue())),
            ])
            ->modifyQueryUsing(function ($query) {
                $courseId = $this->getCourseFilterValue();

                // Всегда подгружаем courses, но с ограничением по courseId, если фильтр выбран.
                $query->with(['courses' => function ($q) use ($courseId) {
                    if ($courseId) {
                        $q->where('courses.id', $courseId);
                    }
                }]);

                return $query;
            })
            ->filters([
                Tables\Filters\SelectFilter::make('course')
                    ->label('Курс')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Course::query()->pluck('name', 'id'))
                    // Автоматически выбирает ID первого курса из таблицы
                    ->default(fn () => Course::query()->value('id'))
                    ->query(function ($query, array $data) {
                        if (blank($data['value'])) {
                            return $query;
                        }
                        return $query->whereHas('courses', function ($q) use ($data) {
                            $q->where('courses.id', $data['value']);
                        });
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->actions([]);
    }

    protected function getCourseFilterValue(): ?string
    {
        return $this->getTable()->getFilter('course')
            ? ($this->tableFilters['course']['value'] ?? null)
            : null;
    }
}

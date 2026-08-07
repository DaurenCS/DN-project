<?php

namespace App\Filament\Curator\Resources;

use App\Filament\Curator\Resources\TestAnalyticsResource\Pages;
use App\Models\Course;
use App\Models\TestAttempt;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestAnalyticsResource extends Resource
{
    // Модель, в которой хранятся результаты тестов
    protected static ?string $model = TestAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Управление обучением';
    protected static ?string $navigationLabel = 'Аналитика тестов';
    protected static ?string $modelLabel = 'Результат теста';
    protected static ?string $pluralModelLabel = 'Аналитика тестов';
    protected static ?string $slug = 'test-analytics';

    // Отключаем возможность создания и редактирования, так как это страница аналитики
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            // Подгружаем связи, чтобы не было проблемы N+1
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'test', 'lesson.module.course']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Студент')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lesson.module.course.name')
                    ->label('Курс')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Можно скрыть по умолчанию, так как фильтруем

                Tables\Columns\TextColumn::make('lesson.name')
                    ->label('Урок')
                    ->sortable(),

                Tables\Columns\TextColumn::make('test.title')
                    ->label('Тест')
                    ->searchable(),

                Tables\Columns\TextColumn::make('correct_answers')
                    ->label('Правильных')
                    ->formatStateUsing(fn ($record) => "{$record->correct_answers} из {$record->total_questions}")
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('percent')
                    ->label('Баллы')
                    ->badge()
                    ->color(fn ($state) => $state >= 70 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        TestAttempt::STATUS_PASSED => 'success',
                        TestAttempt::STATUS_IN_PROGRESS => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('attempts')
                    ->label('Попытки')
                    ->badge(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Дата прохождения')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Создаем единый кастомный фильтр для связки Студент -> Курс
                Tables\Filters\Filter::make('analytics_filter')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Студент')
                            ->options(User::query()->pluck('name', 'id'))
                            ->searchable()
                            ->live() // Обновляет форму при выборе
                            ->afterStateUpdated(fn (Set $set) => $set('course_id', null)), // Сбрасываем курс при смене студента

                        Forms\Components\Select::make('course_id')
                            ->label('Курс')
                            ->options(function (Get $get) {
                                $userId = $get('user_id');

                                // Если студент не выбран, показываем все курсы
                                if (!$userId) {
                                    return Course::query()->pluck('name', 'id');
                                }

                                // Если студент выбран, показываем только его курсы
                                return User::find($userId)?->courses()->pluck('name', 'courses.id') ?? [];
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['user_id'] ?? null,
                                fn (Builder $q, $userId) => $q->where('user_id', $userId)
                            )
                            ->when(
                                $data['course_id'] ?? null,
                                fn (Builder $q, $courseId) => $q->whereHas(
                                    'lesson.module.course',
                                    fn ($q2) => $q2->where('id', $courseId)
                                )
                            );
                    })
                    ->columnSpanFull(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(2) // Делаем 2 колонки, чтобы Студент и Курс стояли на одной линии
            ->actions([]) // Убираем действия со строками (редактирование и тд)
            ->bulkActions([])
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestAnalytics::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\LessonResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VideosRelationManager extends RelationManager
{
    protected static string $relationship = 'videos';
    protected static ?string $title = 'Видео';
    protected static ?string $modelLabel = 'Видео';
    protected static ?string $pluralModelLabel = 'Видео';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Контент')
                    ->schema([
                        Forms\Components\Tabs::make('Языки')
                            ->tabs([
                                $this->getLanguageTab('ru', 'RU (Основной)', true),
                                $this->getLanguageTab('en', 'EN'),
                                $this->getLanguageTab('kz', 'KZ'),
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Параметры видео')
                    ->schema([
                        Forms\Components\TextInput::make('url')
                            ->label('Ссылка на видео')
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->url() // Валидация ссылки
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('duration')
                            ->label('Длительность (в секундах)')
                            ->numeric()
                            ->default(0)
                            ->suffix('сек.'), // Красивая приписка справа
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    /**
     * Генерация вкладок для мультиязычных названий видео
     */
    protected function getLanguageTab(string $lang, string $label, bool $isPrimary = false): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make($label)
            ->schema([
                Forms\Components\TextInput::make("title.{$lang}")
                    ->label('Название видеоролика')
                    ->required($isPrimary)
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // 🌟 ВКЛЮЧАЕМ DRAG-AND-DROP: автоматическая сортировка перетаскиванием
            ->reorderable('sort_order')
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Название видео')
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'ru') ?? '—')
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('title->ru', 'ilike', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('url')
                    ->label('Ссылка')
                    ->limit(40)
                    ->copyable(), // Позволяет скопировать ссылку в 1 клик прямо из таблицы

                Tables\Columns\TextColumn::make('duration')
                    ->label('Длительность')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '00:00';
                        // Форматируем секунды в удобный вид чч:мм:сс или мм:сс
                        return $state >= 3600 ? gmdate('H:i:s', $state) : gmdate('i:s', $state);
                    }),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()->slideOver(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

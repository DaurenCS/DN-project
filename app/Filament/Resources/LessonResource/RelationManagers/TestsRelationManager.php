<?php

namespace App\Filament\Resources\LessonResource\RelationManagers;

use App\Filament\Resources\TestResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TestsRelationManager extends RelationManager
{
    protected static string $relationship = 'tests';
    protected static ?string $title = 'Тесты';
    protected static ?string $modelLabel = 'Тест';
    protected static ?string $pluralModelLabel = 'Тесты';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о тесте')
                    ->schema([
                        Forms\Components\Tabs::make('Языки')
                            ->tabs([
                                $this->getLanguageTab('ru', 'RU (Основной)', true),
                                $this->getLanguageTab('en', 'EN'),
                                $this->getLanguageTab('kz', 'KZ'),
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Параметры')
                    ->schema([
                        Forms\Components\TextInput::make('passing_score')
                            ->label('Проходной балл')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('duration')
                            ->label('Длительность (минуты)')
                            ->numeric()
                            ->helperText('Оставьте пустым или 0, если время не ограничено'),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    protected function getLanguageTab(string $lang, string $label, bool $isPrimary = false): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make($label)
            ->schema([
                Forms\Components\TextInput::make("title.{$lang}")
                    ->label('Название теста')
                    ->required($isPrimary)
                    ->maxLength(255),

                Forms\Components\Textarea::make("description.{$lang}")
                    ->label('Описание')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'ru') ?? '—')
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('title->ru', 'ilike', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('passing_score')
                    ->label('Проходной балл')
                    ->badge(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Вопросов')
                    ->counts('questions')
                    ->badge()
                    ->color('success'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver(),

                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_test')
                    ->label('Настроить вопросы')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->url(fn ($record) => TestResource::getUrl('edit', ['record' => $record])),

                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}

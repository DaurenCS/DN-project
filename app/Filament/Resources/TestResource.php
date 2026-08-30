<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestResource\Pages;
use App\Filament\Resources\TestResource\RelationManagers\QuestionsRelationManager;
use App\Models\Test;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestResource extends Resource
{
    protected static ?string $model = Test::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Управление обучением';
    protected static ?string $modelLabel = 'Тест';
    protected static ?string $pluralModelLabel = 'Тесты';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'curator']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make([
                'default' => 1,
                'lg'      => 3,
            ])
                ->schema([
                    // ── ЛЕВАЯ ЧАСТЬ: контент тестов (2 колонки) ───────────
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make('Основная информация')
                                ->schema([
                                    Forms\Components\Tabs::make('Языки')
                                        ->tabs([
                                            static::getLanguageTab('ru', 'RU (Основной)', true),
                                            static::getLanguageTab('en', 'EN'),
                                            static::getLanguageTab('kz', 'KZ'),
                                        ])
                                        ->persistTabInQueryString(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    // ── ПРАВАЯ ЧАСТЬ: настройки (1 колонка) ──────────────
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make('Параметры прохождения')
                                ->schema([
                                    Forms\Components\TextInput::make('passing_score')
                                        ->label('Проходной балл')
                                        ->numeric()
                                        ->required()
                                        ->default(0)
                                        ->prefix('🎯'),

                                    Forms\Components\TextInput::make('duration')
                                        ->label('Время на прохождение')
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('мин.')
                                        ->helperText('0 — без ограничения по времени'),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ]),
        ])->columns(1);
    }

    protected static function getLanguageTab(string $lang, string $label, bool $isPrimary = false): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make($label)
            ->schema([
                Forms\Components\TextInput::make("title.{$lang}")
                    ->label('Название теста')
                    ->required($isPrimary)
                    ->maxLength(255),

                Forms\Components\Textarea::make("description.{$lang}")
                    ->label('Описание для студента')
                    ->rows(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название теста')
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'ru') ?? '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('title->ru', 'ilike', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('passing_score')
                    ->label('Проходной балл')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Длительность')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} мин." : 'Без лимита'),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Вопросов')
                    ->counts('questions')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTests::route('/'),
            'create' => Pages\CreateTest::route('/create'),
            'edit'   => Pages\EditTest::route('/{record}/edit'),
        ];
    }
}

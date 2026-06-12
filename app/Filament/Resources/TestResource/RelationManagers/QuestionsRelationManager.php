<?php

namespace App\Filament\Resources\TestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';
    protected static ?string $title = 'Вопросы к тесту';
    protected static ?string $modelLabel = 'Вопрос';
    protected static ?string $pluralModelLabel = 'Вопросы';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Настройки самого вопроса
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('question_type_id')
                            ->label('Тип вопроса')
                            ->relationship('type', 'name')
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('point')
                            ->label('Баллы за правильный ответ')
                            ->numeric()
                            ->required()
                            ->default(1),
                    ]),

                // Текст вопроса на разных языках
                Forms\Components\Section::make('Текст вопроса')
                    ->schema([
                        Forms\Components\Tabs::make('Языки вопроса')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('RU (Основной)')
                                    ->schema([
                                        Forms\Components\Textarea::make('question_text.ru')
                                            ->label('Вопрос')
                                            ->required()
                                            ->rows(2),
                                    ]),
                                Forms\Components\Tabs\Tab::make('EN')
                                    ->schema([
                                        Forms\Components\Textarea::make('question_text.en')->label('Вопрос')->rows(2),
                                    ]),
                                Forms\Components\Tabs\Tab::make('KZ')
                                    ->schema([
                                        Forms\Components\Textarea::make('question_text.kz')->label('Вопрос')->rows(2),
                                    ]),
                            ]),
                    ]),

                // 🌟 ВАРИАНТЫ ОТВЕТОВ (Связь HasMany через Repeater)
                Forms\Components\Section::make('Варианты ответов')
                    ->description('Добавьте варианты ответов и отметьте галочкой правильные')
                    ->schema([
                        Forms\Components\Repeater::make('answers')
                            ->relationship('answers') // Имя связи из модели Question
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('answer.ru')
                                            ->label('Ответ (RU)')
                                            ->required(),
                                        Forms\Components\TextInput::make('answer.en')
                                            ->label('Ответ (EN)'),
                                        Forms\Components\TextInput::make('answer.kz')
                                            ->label('Ответ (KZ)'),
                                    ]),

                                Forms\Components\Toggle::make('is_correct')
                                    ->label('Это правильный ответ')
                                    ->inline(false)
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(2) // Сразу генерировать 2 пустых поля для удобства
                            ->createItemButtonLabel('Добавить вариант ответа')
                            ->grid(1)
                            ->itemLabel(fn (array $state): ?string => $state['answer']['ru'] ?? 'Новый вариант'),
                    ]),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question_text')
            ->columns([
                Tables\Columns\TextColumn::make('question_text')
                    ->label('Текст вопроса')
                    ->getStateUsing(fn ($record) => $record->getTranslation('question_text', 'ru') ?? '—')
                    ->limit(70),

                Tables\Columns\TextColumn::make('questionType.name')
                    ->label('Тип')
                    ->badge(),

                Tables\Columns\TextColumn::make('point')
                    ->label('Баллы')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('answers_count')
                    ->label('Вариантов')
                    ->counts('answers')
                    ->badge(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()->slideOver(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\LessonResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ConspectsRelationManager extends RelationManager
{
    protected static string $relationship = 'conspects';
    protected static ?string $title = 'Конспекты';
    protected static ?string $modelLabel = 'Конспект';
    protected static ?string $pluralModelLabel = 'Конспекты';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Контент конспекта')
                    ->schema([
                        Forms\Components\Tabs::make('Языки')
                            ->tabs([
                                $this->getLanguageTab('ru', 'RU (Основной)', true),
                                $this->getLanguageTab('en', 'EN'),
                                $this->getLanguageTab('kz', 'KZ'),
                            ])
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * Генерация вкладок для переводов (аналогично LessonResource)
     */
    protected function getLanguageTab(string $lang, string $label, bool $isPrimary = false): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make($label)
            ->schema([
                Forms\Components\TextInput::make("title.{$lang}")
                    ->label('Заголовок')
                    // По миграции title nullable, поэтому required не ставим жестко,
                    // но можно раскомментировать, если хотите сделать обязательным:
                    // ->required($isPrimary)
                    ->maxLength(255),

                Forms\Components\RichEditor::make("content.{$lang}")
                    ->label('Текст конспекта')
                    ->required($isPrimary) // По миграции content не nullable!
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->recordTitleAttribute('title')
            ->columns([
                // Оставляем колонку с номером для наглядности
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'ru') ?? '—')
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('title->ru', 'ilike', "%{$search}%");
                    }),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()->slideOver(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

use App\Filament\Resources\LessonResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';
    protected static ?string $title = 'Уроки модуля';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Языки')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('RU (Основной)')
                        ->schema([
                            Forms\Components\TextInput::make('name.ru')
                                ->label('Название урока')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                                filled($state) ? $set('slug', Str::slug($state)) : null
                                ),

                            Forms\Components\Textarea::make('description.ru')
                                ->label('Краткое описание')
                                ->rows(2)
                                ->maxLength(500),

                            Forms\Components\RichEditor::make('content.ru')
                                ->label('Контент')
                                ->required()
                                ->fileAttachmentsDisk('public')
                                ->fileAttachmentsDirectory('lessons/attachments')
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Tabs\Tab::make('EN')
                        ->schema([
                            Forms\Components\TextInput::make('name.en')
                                ->label('Название урока')
                                ->maxLength(255),

                            Forms\Components\Textarea::make('description.en')
                                ->label('Краткое описание')
                                ->rows(2)
                                ->maxLength(500),

                            Forms\Components\RichEditor::make('content.en')
                                ->label('Контент')
                                ->fileAttachmentsDisk('public')
                                ->fileAttachmentsDirectory('lessons/attachments')
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Tabs\Tab::make('KZ')
                        ->schema([
                            Forms\Components\TextInput::make('name.kz')
                                ->label('Название урока')
                                ->maxLength(255),

                            Forms\Components\Textarea::make('description.kz')
                                ->label('Краткое описание')
                                ->rows(2)
                                ->maxLength(500),

                            Forms\Components\RichEditor::make('content.kz')
                                ->label('Контент')
                                ->fileAttachmentsDisk('public')
                                ->fileAttachmentsDirectory('lessons/attachments')
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug (URL)')
                        ->required()
                        ->maxLength(255)
                        ->unique('lessons', 'slug', ignoreRecord: true)
                        ->helperText('Генерируется автоматически из названия на RU'),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(fn () => $this->getOwnerRecord()
                                ->lessons()
                                ->max('sort_order') + 1
                        )
                        ->minValue(0)
                        ->prefix('#'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Опубликован')
                        ->default(true)
                        ->helperText('Если выключено, урок будет скрыт для студентов'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(60),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->getStateUsing(fn ($record) =>
                        $record->getTranslation('name', 'ru') ?? '—'
                    )
                    ->description(fn ($record): ?string =>
                        $record->getTranslation('description', 'ru') ?? null
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить урок')
                    ->modalHeading('Создать урок')
                    ->modalWidth('4xl'),  // ширина модалки
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn ($record): string => LessonResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

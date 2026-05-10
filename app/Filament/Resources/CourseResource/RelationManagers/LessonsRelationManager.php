<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';
    protected static ?string $title = 'Уроки курса';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Языки')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('RU')
                        ->schema([
                            Forms\Components\TextInput::make('name.ru')
                                ->label('Название')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    $set('slug', Str::slug($state));
                                }),
                            Forms\Components\Textarea::make('description.ru')
                                ->label('Описание')
                                ->rows(3),
                        ]),
                    Forms\Components\Tabs\Tab::make('EN')
                        ->schema([
                            Forms\Components\TextInput::make('name.en')
                                ->label('Название'),
                            Forms\Components\Textarea::make('description.en')
                                ->label('Описание')
                                ->rows(3),
                        ]),
                    Forms\Components\Tabs\Tab::make('KZ')
                        ->schema([
                            Forms\Components\TextInput::make('name.kz')
                                ->label('Название'),
                            Forms\Components\Textarea::make('description.kz')
                                ->label('Описание')
                                ->rows(3),
                        ]),
                ])
                ->columnSpanFull(),

            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('sort_order')
                ->label('Порядок')
                ->numeric()
                ->default(0),

            Forms\Components\Toggle::make('is_active')
                ->label('Активен')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->getStateUsing(fn ($record) =>
                    $record->getTranslation('name', 'ru')
                        ?: $record->getTranslation('name', 'en')
                        ?: '—'
                    ),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить урок'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Filament\Resources\ModuleResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Filament\Resources\LessonResource;
class ModuleRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';
    protected static ?string $title = 'Модули курса';

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
                        ]),
                    Forms\Components\Tabs\Tab::make('EN')
                        ->schema([
                            Forms\Components\TextInput::make('name.en')
                                ->label('Название'),
                        ]),
                    Forms\Components\Tabs\Tab::make('KZ')
                        ->schema([
                            Forms\Components\TextInput::make('name.kz')
                                ->label('Название'),
                        ]),
                ])
                ->columnSpanFull(),

            Forms\Components\TextInput::make('order')
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
            ->recordUrl(
                fn ($record): string => ModuleResource::getUrl('edit', ['record' => $record]),
            )
            ->recordTitleAttribute('name')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить Модуль'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Редактировать')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn ($record): string => ModuleResource::getUrl('edit', ['record' => $record])),

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

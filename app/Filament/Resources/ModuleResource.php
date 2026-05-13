<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuleResource\Pages;
use App\Filament\Resources\ModuleResource\RelationManagers\LessonsRelationManager;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    // Скрываем из бокового меню, если доступ к модулям идет только через курсы
     protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Управление обучением';
    protected static ?string $modelLabel = 'Модуль';
    protected static ?string $pluralModelLabel = 'Модули';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'name->ru') // Предполагая, что в модели Module есть belongsTo(Course)
                            ->label('Курс')
                            ->disabled()
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('order')
                            ->label('Порядок сортировки')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Переводы')
                    ->schema([
                        Forms\Components\Tabs::make('Языки')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('RU')
                                    ->schema([
                                        Forms\Components\TextInput::make('name.ru')
                                            ->label('Название')
                                            ->required(),
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.name.ru')
                    ->label('Курс')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name.ru')
                    ->label('Название модуля')
                    ->searchable(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->defaultSort('order', 'asc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModules::route('/'),
            'create' => Pages\CreateModule::route('/create'),
            'edit' => Pages\EditModule::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            LessonsRelationManager::class,
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers\ModuleRelationManager;
use App\Models\Course;
use App\Models\CourseType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Управление обучением';
    protected static ?string $navigationLabel = 'Курсы';
    protected static ?string $modelLabel = 'Курс';
    protected static ?string $pluralModelLabel = 'Курсы';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Переводы')
                ->schema([
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
                                        ->rows(4),
                                ]),
                            Forms\Components\Tabs\Tab::make('EN')
                                ->schema([
                                    Forms\Components\TextInput::make('name.en')
                                        ->label('Название'),
                                    Forms\Components\Textarea::make('description.en')
                                        ->label('Описание')
                                        ->rows(4),
                                ]),
                            Forms\Components\Tabs\Tab::make('KZ')
                                ->schema([
                                    Forms\Components\TextInput::make('name.kz')
                                        ->label('Название'),
                                    Forms\Components\Textarea::make('description.kz')
                                        ->label('Описание')
                                        ->rows(4),
                                ]),
                        ])
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Основная информация')
                ->schema([
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('course_type_id')
                        ->label('Тип курса')
                        ->options(
                            CourseType::all()->mapWithKeys(fn ($type) => [
                                $type->id => $type->getTranslation('name', 'ru')
                                    ?: $type->getTranslation('name', 'en')
                                        ?: '—',
                            ])
                        )
                        ->searchable()
                        ->required(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                ])->columns(2),

            Forms\Components\Section::make('Изображение')
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->label('Обложка курса')
                        ->image()
                        ->directory('courses')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Обложка')
                    ->disk('public') // Указываем диск явно
                    ->visibility('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('courseType.name')
                    ->label('Тип')
                    ->badge(),

                Tables\Columns\TextColumn::make('modules_count')
                    ->label('Модули')
                    ->counts('modules')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft'     => 'Черновик',
                        'published' => 'Опубликован',
                        'archived'  => 'Архив',
                    ]),

                Tables\Filters\SelectFilter::make('course_type_id')
                    ->label('Тип курса')
                    ->options(
                        CourseType::all()->mapWithKeys(fn ($type) => [
                            $type->id => $type->getTranslation('name', 'ru')
                                ?: $type->getTranslation('name', 'en')
                                    ?: '—',
                        ])
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ModuleRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit'   => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}

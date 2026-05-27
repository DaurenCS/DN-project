<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Filament\Resources\LessonResource\RelationManagers\ConspectsRelationManager;
use App\Filament\Resources\LessonResource\RelationManagers\TestsRelationManager;
use App\Filament\Resources\LessonResource\RelationManagers\VideosRelationManager;
use App\Models\Lesson;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Управление обучением';
    protected static ?string $modelLabel = 'Урок';
    protected static ?string $pluralModelLabel = 'Уроки';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make([
                'default' => 1,
                'lg'      => 3,
            ])
                ->schema([
                    // ── ЛЕВАЯ ЧАСТЬ: контент (2 колонки) ─────────────────
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make('Содержание урока')
                                ->description('Управление текстом и переводами урока')
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
                            Forms\Components\Section::make('Связи')
                                ->schema([
                                    // Скрытое поле — хранит module_id.
                                    // default() срабатывает при create И createAnother
                                    // (оба раза module_id есть в URL).
                                    Forms\Components\Hidden::make('module_id')
                                        ->default(fn () => request()->query('module_id')),

                                    // Placeholder читает module_id реактивно из состояния формы.
                                    // Работает корректно при create, createAnother и edit.
                                    Forms\Components\Placeholder::make('course_display')
                                        ->label('Курс')
                                        ->content(function (Get $get, $record): string {
                                            $moduleId = $get('module_id')
                                                ?? $record?->module_id
                                                ?? request()->query('module_id');

                                            return Module::find($moduleId)
                                                ?->course
                                                ?->getTranslation('name', 'ru')
                                                ?? '—';
                                        }),

                                    Forms\Components\Placeholder::make('module_display')
                                        ->label('Модуль')
                                        ->content(function (Get $get, $record): string {
                                            $moduleId = $get('module_id')
                                                ?? $record?->module_id
                                                ?? request()->query('module_id');

                                            return Module::find($moduleId)
                                                ?->getTranslation('name', 'ru')
                                                ?? '—';
                                        }),
                                ])
                                ->collapsible(),

                            Forms\Components\Section::make('Параметры')
                                ->schema([
                                    Forms\Components\TextInput::make('slug')
                                        ->label('Slug (URL)')
                                        ->required()
                                        ->unique(Lesson::class, 'slug', ignoreRecord: true),

                                    Forms\Components\TextInput::make('sort_order')
                                        ->label('Порядок')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('#'),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Опубликован')
                                        ->default(true)
                                        ->helperText('Если выключено, урок будет скрыт для студентов'),
                                ]),

                            Forms\Components\Section::make('Информация')
                                ->schema([
                                    Forms\Components\Placeholder::make('created_at')
                                        ->label('Дата создания')
                                        ->content(fn ($record): string => $record?->created_at
                                            ? $record->created_at->diffForHumans()
                                            : '-'),

                                    Forms\Components\Placeholder::make('updated_at')
                                        ->label('Последнее изменение')
                                        ->content(fn ($record): string => $record?->updated_at
                                            ? $record->updated_at->diffForHumans()
                                            : '-'),
                                ])
                                ->hidden(fn ($record) => $record === null)
                                ->collapsible(),
                        ])
                        ->columnSpan(['lg' => 1]),
                ]),
        ])->columns(1);
    }

    protected static function getLanguageTab(string $lang, string $label, bool $isPrimary = false): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make($label)
            ->schema([
                Forms\Components\TextInput::make("name.{$lang}")
                    ->label('Название урока')
                    ->required($isPrimary)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($isPrimary) {
                        if ($isPrimary) {
                            $set('slug', Str::slug($state));
                        }
                    }),

                Forms\Components\Textarea::make("description.{$lang}")
                    ->label('Краткое описание')
                    ->rows(2),

                Forms\Components\RichEditor::make("content.{$lang}")
                    ->label('Контент')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('module.course.name')
                    ->label('Курс')
                    ->getStateUsing(fn ($record) =>
                        $record->module?->course?->getTranslation('name', 'ru') ?? '—')
                    ->color('gray')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('module.course', fn (Builder $q) =>
                        $q->where('name->ru', 'ilike', "%{$search}%")
                        );
                    }),

                Tables\Columns\TextColumn::make('module.name')
                    ->label('Модуль')
                    ->getStateUsing(fn ($record) =>
                        $record->module?->getTranslation('name', 'ru') ?? '—')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название урока')
                    ->getStateUsing(fn ($record) =>
                        $record->getTranslation('name', 'ru') ?? '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name->ru', 'ilike', "%{$search}%");
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'edit'   => Pages\EditLesson::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ConspectsRelationManager::class,
            VideosRelationManager::class,
            TestsRelationManager::class,
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LanguageLineResource\Pages;
use Spatie\TranslationLoader\LanguageLine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LanguageLineResource extends Resource
{
    protected static ?string $model = LanguageLine::class;

    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static ?string $navigationGroup = 'Система';
    protected static ?string $modelLabel = 'Перевод';
    protected static ?string $pluralModelLabel = 'Переводы интерфейса';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ключ перевода')
                    ->description('Как это будет вызываться в коде (например: group "roles", key "admin")')
                    ->schema([
                        Forms\Components\TextInput::make('group')
                            ->label('Группа (файл)')
                            ->required()
                            ->placeholder('Например: roles, messages, buttons'),
                        Forms\Components\TextInput::make('key')
                            ->label('Ключ')
                            ->required()
                            ->placeholder('Например: admin, save, welcome'),
                    ])->columns(2),

                Forms\Components\Section::make('Текст перевода')
                    ->schema([
                        // Так как поле text в модели LanguageLine кастуется в массив,
                        // мы можем напрямую обращаться к ключам языка
                        Forms\Components\TextInput::make('text.ru')
                            ->label('Русский (RU)')
                            ->required(),
                        Forms\Components\TextInput::make('text.kk')
                            ->label('Казахский (KK)'),
                        Forms\Components\TextInput::make('text.en')
                            ->label('Английский (EN)'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Группа')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('key')
                    ->label('Ключ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('text.ru')
                    ->label('Текст (RU)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('text.en')
                    ->label('Текст (EN)')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Фильтр по группе')
                    ->options(fn() => LanguageLine::distinct()->pluck('group', 'group')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLanguageLines::route('/'),
            'create' => Pages\CreateLanguageLine::route('/create'),
            'edit' => Pages\EditLanguageLine::route('/{record}/edit'),
        ];
    }
}

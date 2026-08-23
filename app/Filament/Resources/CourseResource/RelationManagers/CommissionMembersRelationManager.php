<?php
namespace App\Filament\Resources\CourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommissionMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'commissionMembers';
    protected static ?string $title = 'Состав комиссии';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('ФИО'),
                Tables\Columns\TextColumn::make('email')->label('Email'),
                Tables\Columns\TextColumn::make('position')->label('Должность'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Добавить члена комиссии')
                    ->preloadRecordSelect()
                    // Фильтруем выборку пользователей по роли 'commission'
                    ->recordSelectOptionsQuery(fn (Builder $query) =>
                    $query->whereHas('roles', fn (Builder $q) =>
                    $q->where('name', 'commission')
                    )
                    ),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()->label('Удалить из комиссии'),
            ]);
    }
}

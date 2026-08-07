<?php

namespace App\Filament\Curator\Resources;

use App\Filament\Curator\Resources\FlowResource\Pages;
use App\Filament\Curator\Resources\FlowResource\RelationManagers;
use App\Filament\Curator\Resources\FlowResource\RelationManagers\EmployeesRelationManager;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class FlowResource extends Resource
{
    protected static ?string $model = Department::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Управление обучением';
    protected static ?string $navigationLabel = 'Департаменты (обучение)';
    protected static ?string $modelLabel = 'Департамент';
    protected static ?string $pluralModelLabel = 'Департаменты';
    protected static ?string $slug = 'department-staff';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('employees'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->weight('bold'),


                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Сотрудников')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(
                fn ($record) => Pages\ViewFlow::getUrl(['record' => $record])
            )
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            EmployeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [

            'index'  => Pages\ListFlows::route('/'),
            'create' => Pages\CreateFlow::route('/create'),
            'edit'   => Pages\EditFlow::route('/{record}/edit'),
            'view'  => Pages\ViewFlow::route('/{record}'),
        ];
    }
}

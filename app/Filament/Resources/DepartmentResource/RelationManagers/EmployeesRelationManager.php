<?php

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use App\Models\Course;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Tables\Actions\Action::make('attach')
                    ->label('Прикрепить сотрудника')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Сотрудник')
                            ->options(User::whereNull('department_id')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire): void {
                        // $livewire->ownerRecord - это текущий объект Департамента
                        $user = User::find($data['user_id']);
                        $user->update(['department_id' => $livewire->ownerRecord->id]);
                    }),
                Tables\Actions\Action::make('assignToAll')
                    ->label('Назначить курс всему отделу')
                    ->icon('heroicon-o-book-open')
                    ->color('primary')
                    ->form([
                        Select::make('course_id')
                            ->label('Выберите курс')
                            ->options(Course::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function ($livewire, array $data) {
                        $employees = $livewire->ownerRecord->employees;

                        foreach ($employees as $employee) {
                            // Прикрепляем курс, оставляя старт пустым
                            $employee->courses()->syncWithoutDetaching([
                                $data['course_id'] => [
                                    'progress' => 0,
                                    'start_date' => null, // Явно указываем null или просто не передаем его
                                ]
                            ]);
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Назначить курс всему отделу')
                    ->modalDescription('Сотрудники смогут начать обучение, нажав кнопку "Старт" в своем профиле.')
            ])
            ->actions([
//                Tables\Actions\EditAction::make(),
                // Твой кастомный открепитель
                Tables\Actions\Action::make('detach')
                    ->label('Открепить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn ($record) => $record->update(['department_id' => null]))
                    ->requiresConfirmation(),
            ]);

    }
}

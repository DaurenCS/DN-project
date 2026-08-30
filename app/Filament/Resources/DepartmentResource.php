<?php

namespace App\Filament\Resources;

use App\Exports\DepartmentsTemplateExport;
use App\Filament\Resources\DepartmentResource\Pages;
use App\Filament\Resources\DepartmentResource\RelationManagers;
use App\Imports\DepartmentsImport;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Управление персоналом';
    protected static ?string $modelLabel = 'Департамент';
    protected static ?string $pluralModelLabel = 'База департаментов';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'curator']);
    }


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(255),

            // Выбор родительского департамента
            Forms\Components\Select::make('parent_id')
                ->label('Родительское подразделение')
                ->relationship('parent', 'name')
                ->nullable()
                ->searchable(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Родитель'),
                Tables\Columns\TextColumn::make('employees_count')
                    ->counts('employees')
                    ->label('Сотрудников'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('downloadDepartmentsTemplate')
                    ->label('Скачать шаблон')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => Excel::download(new DepartmentsTemplateExport, 'departments_template.xlsx')),

                Tables\Actions\Action::make('importDepartments')
                    ->label('Импорт из Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Excel файл')
                            ->required()
                            ->disk('local')
                            ->directory('imports')
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'text/csv',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $import = new DepartmentsImport;
                        $path = Storage::disk('local')->path($data['file']);

                        Excel::import($import, $path);

                        Storage::disk('local')->delete($data['file']);

                        if ($import->failures()->isNotEmpty()) {
                            Notification::make()
                                ->title('Импорт завершён с ошибками')
                                ->body('Пропущено строк: ' . $import->failures()->count())
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Импорт департаментов завершён')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EmployeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}

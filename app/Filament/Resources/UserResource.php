<?php

namespace App\Filament\Resources;

use App\Enum\Role;
use App\Exports\UsersTemplateExport;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Imports\UsersImport;
use App\Models\Department;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Управление персоналом';
    protected static ?string $modelLabel = 'Сотрудник';
    protected static ?string $pluralModelLabel = 'База сотрудников';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(Role::ADMIN);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Личные данные')
                    ->description('Основная информация о сотруднике')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ФИО')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Корпоративные данные')
                    ->description('Должность и место работы')
                    ->schema([
                        Forms\Components\TextInput::make('position')
                            ->label('Должность'),
                        Forms\Components\Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                            ])
                            ->createOptionUsing(fn (array $data) => Department::create($data)->id),
                    ])->columns(2),

                Forms\Components\Section::make('Доступ и Роли')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Роли пользователя')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => __("roles.{$record->name}"))
                            ->multiple()
                            ->preload(),
                        Forms\Components\TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Подразделение')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Должность'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Был в сети')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name')
                    ->label('Подразделение'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('downloadUsersTemplate')
                    ->label('Скачать шаблон')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => Excel::download(new UsersTemplateExport, 'users_template.xlsx')),

                Tables\Actions\Action::make('importUsers')
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
                        $import = new UsersImport;
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
                            ->title('Импорт сотрудников завершён')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Enum\CertificateStatus;
use App\Models\CertificateApproval;
use App\Models\UserCertificate;
use App\Notifications\CertificateApprovedNotification;
use App\Notifications\CertificateRejectedNotification;
use App\Services\CertificateDocumentService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\UserCertificateResource\Pages;

class UserCertificateResource extends Resource
{
    protected static ?string $model = UserCertificate::class;


    protected static ?string $navigationLabel = 'Заявки на сертификаты';
    protected static ?string $navigationGroup = 'Управление персоналом';
    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $modelLabel = 'Заявки на сертификаты';
    protected static ?string $pluralModelLabel = 'Заявки на сертификаты';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'commission']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', CertificateStatus::PENDING)
            ->whereHas('courseCertificate.course.commissionMembers', function ($query) {
                $query->where('users.id', auth()->id());
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Студент')
                    ->searchable(),
                Tables\Columns\TextColumn::make('courseCertificate.course.name')
                    ->label('Курс'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата заявки')
                    ->dateTime('d.m.Y H:i'),
                // Показываем сколько человек из комиссии уже проголосовали
                Tables\Columns\TextColumn::make('approvals_count')
                    ->counts('approvals')
                    ->label('Голосов «За»'),
            ])
            ->actions([
                // КНОПКА «ОДОБРИТЬ»
                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->hidden(fn (UserCertificate $record) => $record->approvals()->where('commission_user_id', auth()->id())->exists())
                    ->action(function (UserCertificate $record, CertificateDocumentService $documentService) {
                        $isFullyApproved = false;

                        DB::transaction(function () use ($record, $documentService, &$isFullyApproved) {
                            CertificateApproval::create([
                                'user_certificate_id' => $record->id,
                                'commission_user_id'  => auth()->id(),
                                'action'              => 'approve',
                            ]);

                            $course = $record->courseCertificate->course;
                            $requiredVotes = $course->commissionMembers()->count();
                            $currentVotes = $record->approvals()->where('action', 'approve')->count();

                            if ($currentVotes >= $requiredVotes) {
                                $user = $record->user;
                                $variables = [
                                    'date_kz'       => now()->format('d.m.Y'),
                                    'date_ru'       => now()->format('d.m.Y'),
                                    'user_name'     => trim($user->name . ' ' . $user->second_name),
                                    'user_position' => $user->position ?? 'Сотрудник',
                                ];

                                $filePath = $documentService->generateAndUpload(
                                    $record->courseCertificate->certificate->template_path,
                                    $variables
                                );

                                $record->update([
                                    'status'    => CertificateStatus::APPROVED,
                                    'file_path' => $filePath,
                                ]);

                                $isFullyApproved = true;
                            }
                        });

                        // Уведомляем студента после успешного транзакционного коммита
                        $record->user->notify(
                            new CertificateApprovedNotification(
                                certificate: $record,
                                commissionMember: auth()->user(),
                                isFullyApproved: $isFullyApproved
                            )
                        );
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->hidden(fn (UserCertificate $record) => $record->approvals()->where('commission_user_id', auth()->id())->exists())
                    ->requiresConfirmation()
                    ->action(function (UserCertificate $record) {
                        DB::transaction(function () use ($record) {
                            CertificateApproval::create([
                                'user_certificate_id' => $record->id,
                                'commission_user_id'  => auth()->id(),
                                'action'              => 'reject',
                            ]);

                            $record->update(['status' => CertificateStatus::REJECTED]);
                        });

                        // Уведомляем студента об отклонении
                        $record->user->notify(
                            new CertificateRejectedNotification(
                                certificate: $record,
                                commissionMember: auth()->user()
                            )
                        );
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserCertificates::route('/'),
            'create' => Pages\CreateUserCertificate::route('/create'),
            'edit' => Pages\EditUserCertificate::route('/{record}/edit'),
        ];
    }
}

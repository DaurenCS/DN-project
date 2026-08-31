<?php
namespace App\Notifications;

use App\DTO\NotificationPayload;
use App\Filament\Resources\UserCertificateResource;
use App\Models\UserCertificate;

class CertificateApprovalNotification extends BaseNotification
{
    public function __construct(
        public readonly UserCertificate $certificate
    ) {}

    protected function getPayload(object $notifiable): NotificationPayload
    {
        $courseName = $this->certificate->courseCertificate->course->name;

        return new NotificationPayload(
            title: "Новая заявка на одобрение: {$courseName}",
            body: "Поступила новая заявка на выдачу сертификата по курсу **{$courseName}**. Пожалуйста, авторизуйтесь в системе и подтвердите выдачу.",
            actionUrl: UserCertificateResource::getUrl('index'),
            actionText: 'Перейти в личный кабинет для проверки',
            extraData: [
                'certificate_id' => $this->certificate->id,
                'course_name' => $courseName,
            ]
        );
    }
}

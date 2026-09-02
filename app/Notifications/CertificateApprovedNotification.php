<?php
namespace App\Notifications;

use App\DTO\NotificationPayload;
use App\Models\UserCertificate;
use App\Models\User;

class CertificateApprovedNotification extends BaseNotification
{
    public function __construct(
        public readonly UserCertificate $certificate,
        public readonly User $commissionMember,
        public readonly bool $isFullyApproved = false
    ) {}

    protected function getPayload(object $notifiable): NotificationPayload
    {
        $courseName = $this->certificate->courseCertificate->course->name;

        if ($this->isFullyApproved) {
            return new NotificationPayload(
                title: "Ваш сертификат по курсу \"{$courseName}\" готов!",
                body: "Член комиссии **{$this->commissionMember->name}** поставил финальное одобрение. Сертификат успешно сформирован и доступен для скачивания.",
                actionUrl: null,
                actionText: 'Скачать сертификат',
                extraData: [
                    'certificate_id' => $this->certificate->id,
                    'status' => 'approved',
                ]
            );
        }

        return new NotificationPayload(
            title: "Голос «За» по сертификату: {$courseName}",
            body: "Член комиссии **{$this->commissionMember->name}** одобрил вашу заявку на сертификат. Ожидаются решения остальных членов комиссии.",
            actionUrl: null,
            extraData: [
                'certificate_id' => $this->certificate->id,
                'status' => 'pending_votes',
            ]
        );
    }
}

<?php
namespace App\Notifications;

use App\DTO\NotificationPayload;
use App\Models\UserCertificate;
use App\Models\User;

class CertificateRejectedNotification extends BaseNotification
{
    public function __construct(
        public readonly UserCertificate $certificate,
        public readonly User $commissionMember
    ) {}

    protected function getPayload(object $notifiable): NotificationPayload
    {
        $courseName = $this->certificate->courseCertificate->course->name;

        return new NotificationPayload(
            title: "Заявка на сертификат отклонена",
            body: "К сожалению, член комиссии **{$this->commissionMember->name}** отклонил вашу заявку на получение сертификата по курсу \"{$courseName}\".",
            actionUrl: null,
            extraData: [
                'certificate_id' => $this->certificate->id,
                'status' => 'rejected',
            ]
        );
    }
}

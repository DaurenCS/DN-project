<?php
namespace App\Notifications;

use App\DTO\NotificationPayload;
use App\Models\UserCertificate;

class CertificateExpiringNotification extends BaseNotification
{
    public function __construct(
        public readonly UserCertificate $certificate,
        public readonly int $daysLeft
    ) {}

    protected function getPayload(object $notifiable): NotificationPayload
    {
        $courseName = $this->certificate->courseCertificate->course->name;

        return new NotificationPayload(
            title: "Истекает срок действия сертификата",
            body: "Обратите внимание! До окончания срока действия вашего сертификата по курсу **\"{$courseName}\"** осталось **{$this->daysLeft} d.**.",
            actionUrl: null,
            extraData: [
                'certificate_id' => $this->certificate->id,
                'days_left' => $this->daysLeft,
            ]
        );
    }
}

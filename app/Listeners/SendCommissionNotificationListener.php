<?php
namespace App\Listeners;

use App\Events\CertificateRequested;
use App\Models\User;
use App\Notifications\CertificateApprovalNotification;
use Illuminate\Support\Facades\Notification;

class SendCommissionNotificationListener
{
    public function handle(CertificateRequested $event): void
    {
        $certificate = $event->certificate;

        $course = $certificate->getCourseAttribute();

        $commissionMembers = $course->commissionMembers;

        if ($commissionMembers->isNotEmpty()) {
            Notification::send($commissionMembers, new CertificateApprovalNotification($certificate));
        }
    }
}

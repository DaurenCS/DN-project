<?php

namespace App\Listeners;

use App\Events\CertificateRequested;
use App\Notifications\CertificateApprovalNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendCommissionNotificationListener implements ShouldQueue
{
    public function handle(CertificateRequested $event): void
    {
        $commissionMembers = $event->certificate->course->commissionMembers->unique('id');

        if ($commissionMembers->isNotEmpty()) {
            Notification::send($commissionMembers, new CertificateApprovalNotification($event->certificate));
        }
    }
}

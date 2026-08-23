<?php
namespace App\Notifications;

use App\Filament\Resources\PendingCertificateResource;
use App\Filament\Resources\UserCertificateResource;
use App\Models\UserCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class CertificateApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public UserCertificate $certificate) {}

    // Здесь в будущем добавите 'sms'
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
    public function toMail(object $notifiable): MailMessage
    {
        $courseName = $this->certificate->courseCertificate->course->name;
        $adminUrl = UserCertificateResource::getUrl('index');

        return (new MailMessage)
            ->subject("Новая заявка на одобрение: {$courseName}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Поступила новая заявка на выдачу сертификата по курсу **{$courseName}**.")
            ->action('Перейти в личный кабинет для проверки', $adminUrl)
            ->line('Пожалуйста, авторизуйтесь в системе и подтвердите выдачу.');
    }

//    public function toMail(object $notifiable): MailMessage
//    {
//        // Генерируем безопасные ссылки с привязкой к ID члена комиссии
//        $approveUrl = URL::temporarySignedRoute('certificates.process', now()->addDays(7), [
//            'certificate_id' => $this->certificate->id,
//            'commission_user_id' => $notifiable->id,
//            'action' => 'approve'
//        ]);
//
//        $rejectUrl = URL::temporarySignedRoute('certificates.process', now()->addDays(7), [
//            'certificate_id' => $this->certificate->id,
//            'commission_user_id' => $notifiable->id,
//            'action' => 'reject'
//        ]);
//
//        return (new MailMessage)
//            ->subject('Новая заявка на сертификат')
//            ->line("Пользователь {$this->certificate->user->name} ожидает выдачи сертификата.")
//            ->action('✅ Одобрить', $approveUrl)
//            ->line("❌ [Отклонить]({$rejectUrl})");
//    }

    // Заготовка под SMS на будущее
    /*
    public function toSms(object $notifiable) {
        return "Новая заявка на сертификат #{$this->certificate->id}.";
    }
    */
}

<?php
namespace App\Notifications;

use App\DTO\NotificationPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $timeout = 30;
    public $tries = 3;


    abstract protected function getPayload(object $notifiable): NotificationPayload;

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        return $channels;
    }

//    public function toMail(object $notifiable): MailMessage
//    {
//        $payload = $this->getPayload($notifiable);
//
//        $mail = (new MailMessage)
//            ->subject($payload->title)
//            ->greeting("Здравствуйте, {$notifiable->name}!")
//            ->line($payload->body);
//
//        if ($payload->actionUrl) {
//            $mail->action($payload->actionText, $payload->actionUrl);
//        }
//
//        return $mail;
//    }

    public function toArray(object $notifiable): array
    {
        $payload = $this->getPayload($notifiable);

        return array_merge([
            'title' => $payload->title,
            'message' => $payload->body,
            'url' => $payload->actionUrl,
        ], $payload->extraData);
    }

//    public function toFcm(object $notifiable): FcmMessage
//    {
//        $payload = $this->getPayload($notifiable);
//
//        return FcmMessage::create()
//            ->setData($payload->extraData)
//            ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
//                ->setTitle($payload->title)
//                ->setBody($payload->body)
//            );
//    }
}

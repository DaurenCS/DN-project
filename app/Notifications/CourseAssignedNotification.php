<?php
namespace App\Notifications;

use App\DTO\NotificationPayload;
use App\Models\Course;

class CourseAssignedNotification extends BaseNotification
{
    public function __construct(
        public readonly Course $course
    ) {}

    protected function getPayload(object $notifiable): NotificationPayload
    {
        return new NotificationPayload(
            title: "Назначен новый курс: {$this->course->name}",
            body: "За вами был прикреплен новый курс **\"{$this->course->name}\"**. Вы можете приступить к обучению в личном кабинете.",
            actionUrl: null,
            actionText: 'Перейти к курсу',
            extraData: [
                'course_id' => $this->course->id,
                'course_name' => $this->course->name,
            ]
        );
    }
}

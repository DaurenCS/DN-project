<?php

namespace App\DTO;

class NotificationPayload
{
    public function __construct(
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
        public ?string $actionText = 'Перейти',
        public array $extraData = []
    ) {}
}

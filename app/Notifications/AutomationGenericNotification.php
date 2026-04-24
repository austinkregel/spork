<?php

declare(strict_types=1);

namespace App\Notifications;

class AutomationGenericNotification extends AbstractNotification
{
    public function __construct(
        protected string $title,
        protected string $message,
        protected string $level = 'info'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'level' => $this->level,
        ];
    }
}

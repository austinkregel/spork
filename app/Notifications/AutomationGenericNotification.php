<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\AbstractNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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



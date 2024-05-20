<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

abstract class AbstractNotification extends Notification
{
    use Queueable;

    public static function getNotificationOptions(): array
    {
        return [];
    }
}

<?php

namespace App\Notifications\Daily;

use App\Forecast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SummaryNotification extends Notification
{
    use Queueable;

    public function __construct(
        // weather forecast near a user
        // article headlines
        // Transactions from a period,
        // domain expirations
        public ?Forecast $weather,
        public ?array $articles,
        public Collection $transactions,
        public Collection $accounts,
    )
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage);
        $message->view(
            'emails.daily.summary',
            [
                'weather' => $this->weather,
                'articles' => $this->articles,
                'transactions' => $this->transactions,
                'accounts' => $this->accounts,
            ]
        );
        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}

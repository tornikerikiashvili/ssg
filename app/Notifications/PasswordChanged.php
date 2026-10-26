<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct()
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your SmartSoft Client Area password has changed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The password for your SmartSoft Client Area account has been changed.')
            ->line('If you made this change, no further action is needed.')
            ->line('If you did not make this change, please contact the SmartSoft administration or your SmartSoft representative immediately.')
            ->salutation('SmartSoft Team');
    }
}

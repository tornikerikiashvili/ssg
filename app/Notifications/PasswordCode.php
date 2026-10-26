<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordCode extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $code)
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
        return (new MailMessage)->subject('Your SmartSoft verification code')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your verification code is: '.$this->code)
            ->line('This code expires in 10 minutes. Never share it with anyone.')
            ->line('If you did not request this, ignore this email and contact your SmartSoft representative.')
            ->salutation('SmartSoft Team');
    }
}

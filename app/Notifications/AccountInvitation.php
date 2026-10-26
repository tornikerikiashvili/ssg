<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountInvitation extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $temporaryPassword)
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
        return (new MailMessage)->subject('Your SmartSoft Client Area invitation')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your username is: '.$notifiable->email)
            ->line('Your temporary password is: '.$this->temporaryPassword)
            ->line('This password expires in 24 hours and can be used once. After signing in, confirm the four-digit code sent to your email and set your own password.')
            ->action('Sign in', route('login'))
            ->salutation('SmartSoft Team');
    }
}

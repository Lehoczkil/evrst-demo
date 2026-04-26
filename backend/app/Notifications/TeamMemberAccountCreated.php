<?php

namespace App\Notifications;

use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamMemberAccountCreated extends Notification
{
    use Queueable;

    public function __construct(public string $temporaryPassword)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = Filament::getLoginUrl();

        return (new MailMessage())
            ->subject('Your EVRST admin account')
            ->greeting('Welcome to EVRST!')
            ->line('A new admin account has been created for you on the EVRST team panel.')
            ->line('You can sign in with your email and the temporary password below — you will be asked to choose a new password on first login.')
            ->line('Email: ' . $notifiable->email)
            ->line('Temporary password: ' . $this->temporaryPassword)
            ->action('Open the admin panel', $loginUrl)
            ->line('If you were not expecting this email, please ignore it.');
    }
}

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
        $login = $notifiable->email;
        $delivery = method_exists($notifiable, 'deliveryEmail') ? $notifiable->deliveryEmail() : null;

        $mail = (new MailMessage())
            ->subject(__('admin.mail.account_subject'))
            ->greeting(__('admin.mail.account_greeting'))
            ->line(__('admin.mail.account_intro'))
            ->line(__('admin.mail.account_login', ['email' => $login]))
            ->line(__('admin.mail.account_password', ['password' => $this->temporaryPassword]))
            ->action(__('admin.mail.account_cta'), self::loginUrl())
            // The address format trips people up more than the password
            // does: it looks like a mailbox, so say plainly that it isn't.
            ->line(__('admin.mail.account_format'))
            ->line(__('admin.mail.account_first_login'));

        // Spell out the split when the login isn't the inbox this landed
        // in — otherwise "sign in with laszlo.lehoczki@evrst.hu" arriving
        // at a Gmail address reads like a mistake.
        if ($delivery && $delivery !== $login) {
            $mail->line(__('admin.mail.account_split', [
                'delivery' => $delivery,
                'login' => $login,
            ]));
        }

        $mail->line(__('admin.mail.account_forgot', [
            'login' => $login,
            'delivery' => $delivery ?? $login,
        ]));

        return $mail->line(__('admin.mail.account_ignore'));
    }

    /**
     * Filament::getLoginUrl() needs a booted panel, which is there for the
     * in-request admin actions that send this mail but not guaranteed from
     * a queue worker or `artisan` context. This mail is the one nobody can
     * afford to receive with a broken link, so fall back to the configured
     * app URL rather than throwing.
     */
    private static function loginUrl(): string
    {
        try {
            return Filament::getLoginUrl();
        } catch (\Throwable) {
            return rtrim((string) config('app.url'), '/') . '/admin/login';
        }
    }
}

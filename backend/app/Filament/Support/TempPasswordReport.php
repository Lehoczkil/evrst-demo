<?php

namespace App\Filament\Support;

use App\Actions\IssueTempPassword;
use App\Actions\TempPasswordResult;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Turns an {@see TempPasswordResult} into the panel notification the admin
 * sees. Shared by every caller so the wording — and, more importantly, the
 * address reported — cannot drift again.
 */
final class TempPasswordReport
{
    /** One account: the row action, "Create login", CreateUser. */
    public static function flash(TempPasswordResult $result): void
    {
        match ($result->status) {
            IssueTempPassword::SENT => $result->loggedNotSent()
                ? Notification::make()
                    ->title(__('admin.users.temp_logged'))
                    ->body(__('admin.users.temp_logged_body', ['email' => $result->destination ?? '—']))
                    ->warning()
                    ->persistent()
                    ->send()
                : Notification::make()
                    ->title(__('admin.users.temp_sent'))
                    ->body(__('admin.users.temp_sent_body', ['email' => $result->destination ?? '—']))
                    ->success()
                    ->send(),

            IssueTempPassword::SKIPPED_UNDELIVERABLE => Notification::make()
                ->title(__('admin.users.temp_skipped'))
                ->body(__('admin.users.temp_skipped_body'))
                ->warning()
                ->persistent()
                ->send(),

            IssueTempPassword::FAILED => Notification::make()
                ->title(__('admin.users.temp_send_failed'))
                ->body($result->error ?? '')
                ->danger()
                ->persistent()
                ->send(),

            default => null,
        };
    }

    /**
     * A batch: one notification listing what was sent, skipped and failed.
     *
     * @param  array<int, string>  $sent
     * @param  array<int, string>  $skipped
     * @param  array<int, string>  $failed
     */
    public static function flashBatch(int $total, array $sent, array $skipped, array $failed): void
    {
        $lines = [__('admin.users.bulk_temp_done_body', ['sent' => count($sent), 'total' => $total])];

        if ($skipped !== []) {
            $lines[] = __('admin.users.bulk_temp_skipped', ['names' => implode(', ', $skipped)]);
        }

        if ($failed !== []) {
            $lines[] = __('admin.users.bulk_temp_failed', ['names' => implode(', ', $failed)]);
        }

        // MAIL_MAILER=log writes the passwords to the log instead of sending
        // them — say so loudly, same as the single-record path does.
        $logged = config('mail.default') === 'log';
        if ($logged && $sent !== []) {
            $lines[] = __('admin.users.temp_logged_body', ['email' => implode(', ', $sent)]);
        }

        $body = new HtmlString(implode('<br>', array_map('e', $lines)));

        $notification = Notification::make()->body($body);

        if ($sent === []) {
            $notification->title(__('admin.users.bulk_temp_none'))->warning()->persistent();
        } elseif ($logged || $skipped !== [] || $failed !== []) {
            $notification->title($logged ? __('admin.users.temp_logged') : __('admin.users.bulk_temp_done'))
                ->warning()
                ->persistent();
        } else {
            $notification->title(__('admin.users.bulk_temp_done'))->success();
        }

        $notification->send();
    }
}

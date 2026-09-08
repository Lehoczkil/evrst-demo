<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Role;
use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use App\Support\OrgEmail;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.common.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')->toggleable(),
                TextColumn::make('email')
                    ->label(__('admin.users.login_email'))
                    ->searchable()
                    ->copyable()
                    ->color('gray')->toggleable(),
                TextColumn::make('notification_email')
                    ->label(__('admin.users.notification_email'))
                    // Mirrors User::deliveryEmail() rather than re-deriving
                    // it, so the column can't drift from where mail actually
                    // goes when mail.deliver_to_org_addresses is flipped.
                    ->state(fn (User $r) => $r->deliveryEmail() ?? '—')
                    ->copyable()
                    ->badge()
                    ->color(fn (User $r) => $r->deliveryEmail() === $r->email ? 'gray' : 'success')
                    ->tooltip(fn (User $r) => $r->deliveryEmail() === $r->email
                        ? __('admin.users.notification_email_account_tip')
                        : __('admin.users.notification_email_private_tip'))
                    ->toggleable(),
                TextColumn::make('role.name')
                    ->label(__('admin.common.role'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'Admin' => __('admin.roles.admin'),
                        'Manager' => __('admin.roles.manager'),
                        'Member' => __('admin.roles.member'),
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'Admin' => 'danger',
                        'Manager' => 'warning',
                        default => 'gray',
                    })->toggleable(),
                IconColumn::make('password_changed_at')
                    ->label(__('admin.users.password_set'))
                    ->boolean()
                    ->getStateUsing(fn (User $r) => $r->password_changed_at !== null)->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->label(__('admin.common.role'))
                    ->options(fn () => \Illuminate\Support\Facades\Cache::remember(
                        'options:roles',
                        300,
                        fn () => Role::orderBy('name')->pluck('name', 'id')->all(),
                    )),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('resend_temp_password')
                    ->label(__('admin.users.resend_temp'))
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.users.resend_modal'))
                    ->action(function (User $record) {
                        $temp = Str::password(12);
                        $record->update([
                            'password' => Hash::make($temp),
                            'password_changed_at' => null,
                        ]);

                        $destination = $record->fresh('teamMember')->deliveryEmail();

                        // sendNow bypasses the queue so the admin gets
                        // immediate feedback instead of "queued" silence
                        // when no worker is running on the deploy box.
                        try {
                            NotificationFacade::sendNow($record, new TeamMemberAccountCreated($temp));
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title(__('admin.users.temp_send_failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        $mailer = config('mail.default');
                        if ($mailer === 'log') {
                            Notification::make()
                                ->title(__('admin.users.temp_logged'))
                                ->body(__('admin.users.temp_logged_body', ['email' => $destination ?? '—']))
                                ->warning()
                                ->persistent()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title(__('admin.users.temp_sent'))
                            ->body(__('admin.users.temp_sent_body', ['email' => $destination ?? $record->email]))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (User $r) => $r->id !== auth()->id()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('send_temp_password')
                        ->label(__('admin.users.bulk_temp'))
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.users.bulk_temp_modal'))
                        ->modalDescription(__('admin.users.bulk_temp_modal_body'))
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (EloquentCollection $records) => self::sendTempPasswords($records)),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.empty.users_h'))
            ->emptyStateDescription(__('admin.empty.users_b'))
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    /**
     * Onboarding in one click: rotate a temp password for every selected
     * account and mail it to wherever that user's mail actually goes.
     *
     * Sent with sendNow (like the single-record action) so the report below
     * reflects real delivery instead of "queued" silence, and so this works
     * on a box with no queue worker running.
     *
     * @param  EloquentCollection<int, User>  $records
     */
    private static function sendTempPasswords(EloquentCollection $records): void
    {
        $records->loadMissing('teamMember');

        $sent = [];
        $skipped = [];
        $failed = [];

        foreach ($records as $record) {
            $destination = $record->deliveryEmail();

            // Rotate only when there is somewhere to send it. Otherwise we
            // would invalidate the member's current password and hand the
            // replacement to a mailbox that doesn't exist — locking them out.
            if (! self::isDeliverable($destination)) {
                $skipped[] = $record->name;

                continue;
            }

            $temp = Str::password(12);
            $record->update([
                'password' => Hash::make($temp),
                'password_changed_at' => null,
            ]);

            try {
                NotificationFacade::sendNow($record, new TeamMemberAccountCreated($temp));
                $sent[] = $record->name;
            } catch (\Throwable $e) {
                // One bad address must not abort the rest of the batch.
                report($e);
                $failed[] = $record->name;
            }
        }

        self::reportTempPasswordBatch($records->count(), $sent, $skipped, $failed);
    }

    /**
     * deliveryEmail() falls back to the login address when no private one is
     * on file. While MAIL_DELIVER_TO_ORG is off that fallback is an @evrst.hu
     * address, which is a sign-in name with no mailbox behind it — mail to it
     * bounces, so treat it as undeliverable.
     */
    private static function isDeliverable(?string $destination): bool
    {
        if ($destination === null) {
            return false;
        }

        return (bool) config('mail.deliver_to_org_addresses')
            || ! OrgEmail::isOrgAddress($destination);
    }

    /**
     * @param  array<int, string>  $sent
     * @param  array<int, string>  $skipped
     * @param  array<int, string>  $failed
     */
    private static function reportTempPasswordBatch(int $total, array $sent, array $skipped, array $failed): void
    {
        $lines = [__('admin.users.bulk_temp_done_body', ['sent' => count($sent), 'total' => $total])];

        if ($skipped !== []) {
            $lines[] = __('admin.users.bulk_temp_skipped', ['names' => implode(', ', $skipped)]);
        }

        if ($failed !== []) {
            $lines[] = __('admin.users.bulk_temp_failed', ['names' => implode(', ', $failed)]);
        }

        // MAIL_MAILER=log writes the passwords to the log instead of sending
        // them — say so loudly, same as the single-record action does.
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

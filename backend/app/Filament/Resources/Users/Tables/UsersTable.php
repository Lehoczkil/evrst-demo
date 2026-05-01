<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Role;
use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
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
                    ->label(__('admin.common.email'))
                    ->searchable()
                    ->copyable()
                    ->color('gray')->toggleable(),
                TextColumn::make('notification_email')
                    ->label(__('admin.users.notification_email'))
                    ->state(fn (User $r) => $r->teamMember?->email_private ?: $r->email)
                    ->copyable()
                    ->badge()
                    ->color(fn (User $r) => $r->teamMember?->email_private ? 'success' : 'gray')
                    ->tooltip(fn (User $r) => $r->teamMember?->email_private
                        ? __('admin.users.notification_email_private_tip')
                        : __('admin.users.notification_email_fallback_tip'))
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

                        $route = $record->routeNotificationForMail(null);
                        $destination = $route ? array_key_first($route) : null;

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
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.empty.users_h'))
            ->emptyStateDescription(__('admin.empty.users_b'))
            ->emptyStateIcon('heroicon-o-shield-check');
    }
}

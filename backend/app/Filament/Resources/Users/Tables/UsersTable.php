<?php

namespace App\Filament\Resources\Users\Tables;

use App\Actions\IssueTempPassword;
use App\Filament\Support\TempPasswordReport;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Eager-load what the columns read — Filament does no
            // automatic eager loading, so without this the role column and deliveryEmail(), which the notification_email column calls from three closures
            // fire one query per row.
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['role:id,name', 'teamMember']))
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
                    // Same action as the bulk one below, on one row: it
                    // refuses an undeliverable address instead of rotating
                    // into the void, and rolls back if the send throws.
                    ->action(fn (User $record) => TempPasswordReport::flash(
                        IssueTempPassword::rotate($record),
                    )),
                // The person stays on the roster; only the account goes.
                // Say so, or "delete user" reads as "delete member".
                DeleteAction::make()
                    ->visible(fn (User $r) => $r->id !== auth()->id())
                    ->modalDescription(fn (User $record) => $record->teamMember
                        ? __('admin.users.delete_keeps_member_body', ['name' => $record->teamMember->name])
                        : __('filament-actions::delete.single.modal.description')),
            ])
            ->toolbarActions([
                // Deliberately NOT inside the BulkActionGroup dropdown.
                // This is the roster onboarding path, and burying it next to
                // "Delete selected" made it indistinguishable from the
                // per-row action — a real run selected every row, clicked the
                // row button, and mailed exactly one person.
                BulkAction::make('send_temp_password')
                    ->label(__('admin.users.bulk_temp'))
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.users.bulk_temp_modal'))
                    ->modalDescription(__('admin.users.bulk_temp_modal_body'))
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (EloquentCollection $records) => self::sendTempPasswords($records)),
                BulkActionGroup::make([
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
     * The per-record work is {@see IssueTempPassword::rotate()} — including
     * the skip for members with no deliverable address, because rotating a
     * password we cannot deliver locks them out. One bad address must not
     * abort the rest of the batch, so the report is assembled at the end.
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
            $result = IssueTempPassword::rotate($record);

            match ($result->status) {
                IssueTempPassword::SENT => $sent[] = $record->name,
                IssueTempPassword::SKIPPED_UNDELIVERABLE => $skipped[] = $record->name,
                IssueTempPassword::FAILED => $failed[] = $record->name,
                default => null,
            };
        }

        TempPasswordReport::flashBatch($records->count(), $sent, $skipped, $failed);
    }
}

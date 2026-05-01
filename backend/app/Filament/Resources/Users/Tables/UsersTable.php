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
                        $record->notify(new TeamMemberAccountCreated($temp));
                        Notification::make()
                            ->title(__('admin.users.temp_sent'))
                            ->body(__('admin.users.temp_sent_body', ['email' => $record->email]))
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

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
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),
                TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Admin' => 'danger',
                        'Manager' => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('password_changed_at')
                    ->label('Password set')
                    ->boolean()
                    ->getStateUsing(fn (User $r) => $r->password_changed_at !== null),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->label('Role')
                    ->options(fn () => Role::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('resend_temp_password')
                    ->label('Resend temp password')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send a new temporary password?')
                    ->action(function (User $record) {
                        $temp = Str::password(12);
                        $record->update([
                            'password' => Hash::make($temp),
                            'password_changed_at' => null,
                        ]);
                        $record->notify(new TeamMemberAccountCreated($temp));
                        Notification::make()
                            ->title('Temporary password emailed')
                            ->body('A new temp password has been sent to ' . $record->email . '.')
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
            ]);
    }
}

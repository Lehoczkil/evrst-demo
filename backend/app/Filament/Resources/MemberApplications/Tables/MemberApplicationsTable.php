<?php

namespace App\Filament\Resources\MemberApplications\Tables;

use App\Auth\Perm;
use App\Filament\Resources\MemberApplications\MemberApplicationResource;
use App\Jobs\PostDiscordWebhook;
use App\Models\MemberApplication;
use App\Support\DiscordPayloads;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MemberApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.common.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->label(__('admin.common.email'))
                    ->searchable()
                    ->copyable()
                    ->color('gray'),
                TextColumn::make('department')
                    ->label(__('admin.applications.department'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label(__('admin.common.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.applications.statuses.' . $state))
                    ->colors([
                        'warning' => MemberApplication::STATUS_PENDING,
                        'success' => MemberApplication::STATUS_ACCEPTED,
                        'danger' => MemberApplication::STATUS_REJECTED,
                    ])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.submitted_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.common.status'))
                    ->options([
                        MemberApplication::STATUS_PENDING => __('admin.applications.statuses.PENDING'),
                        MemberApplication::STATUS_ACCEPTED => __('admin.applications.statuses.ACCEPTED'),
                        MemberApplication::STATUS_REJECTED => __('admin.applications.statuses.REJECTED'),
                    ])
                    ->default(MemberApplication::STATUS_PENDING),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('accept')
                    ->label(__('admin.applications.accept'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MemberApplication $record) => $record->status !== MemberApplication::STATUS_ACCEPTED
                        && (auth()->user()?->can(Perm::APPLICATIONS_ACCEPT) ?? false))
                    ->url(fn (MemberApplication $record) => MemberApplicationResource::getUrl('accept', ['record' => $record])),
                Action::make('reject')
                    ->label(__('admin.applications.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.applications.reject_modal'))
                    ->visible(fn (MemberApplication $record) => $record->status !== MemberApplication::STATUS_REJECTED
                        && (auth()->user()?->can(Perm::APPLICATIONS_REFUSE) ?? false))
                    ->action(function (MemberApplication $record) {
                        $record->withoutActivityLog(fn () => $record->update([
                            'status' => MemberApplication::STATUS_REJECTED,
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]));
                        $record->logActivity('rejected');
                        $payload = DiscordPayloads::applicationRejected($record, auth()->user());
                        PostDiscordWebhook::dispatch($payload['content'], $payload['embed'], $payload['reference']);
                        Notification::make()->title(__('admin.applications.rejected_msg'))->warning()->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

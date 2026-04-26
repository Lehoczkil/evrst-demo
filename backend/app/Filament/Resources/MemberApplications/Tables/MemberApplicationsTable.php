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
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),
                TextColumn::make('department')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => MemberApplication::STATUS_PENDING,
                        'success' => MemberApplication::STATUS_ACCEPTED,
                        'danger' => MemberApplication::STATUS_REJECTED,
                    ])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        MemberApplication::STATUS_PENDING => 'Pending',
                        MemberApplication::STATUS_ACCEPTED => 'Accepted',
                        MemberApplication::STATUS_REJECTED => 'Rejected',
                    ])
                    ->default(MemberApplication::STATUS_PENDING),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MemberApplication $record) => $record->status !== MemberApplication::STATUS_ACCEPTED
                        && (auth()->user()?->can(Perm::APPLICATIONS_ACCEPT) ?? false))
                    ->url(fn (MemberApplication $record) => MemberApplicationResource::getUrl('accept', ['record' => $record])),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject application?')
                    ->visible(fn (MemberApplication $record) => $record->status !== MemberApplication::STATUS_REJECTED
                        && (auth()->user()?->can(Perm::APPLICATIONS_REFUSE) ?? false))
                    ->action(function (MemberApplication $record) {
                        $record->update([
                            'status' => MemberApplication::STATUS_REJECTED,
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);
                        $payload = DiscordPayloads::applicationRejected($record, auth()->user());
                        PostDiscordWebhook::dispatch($payload['content'], $payload['embed'], $payload['reference']);
                        Notification::make()->title('Application rejected')->warning()->send();
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

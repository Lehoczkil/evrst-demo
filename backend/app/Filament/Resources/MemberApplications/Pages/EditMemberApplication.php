<?php

namespace App\Filament\Resources\MemberApplications\Pages;

use App\Auth\Perm;
use App\Filament\Resources\MemberApplications\MemberApplicationResource;
use App\Jobs\PostDiscordWebhook;
use App\Models\MemberApplication;
use App\Support\DiscordPayloads;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMemberApplication extends EditRecord
{
    protected static string $resource = MemberApplicationResource::class;

    protected function getHeaderActions(): array
    {
        /** @var MemberApplication $record */
        $record = $this->getRecord();

        return [
            Action::make('accept')
                ->label(__('admin.applications.accept'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible($record->status !== MemberApplication::STATUS_ACCEPTED
                    && (auth()->user()?->can(Perm::APPLICATIONS_ACCEPT) ?? false))
                ->url(MemberApplicationResource::getUrl('accept', ['record' => $record])),
            Action::make('reject')
                ->label(__('admin.applications.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('admin.applications.reject_modal'))
                ->visible($record->status !== MemberApplication::STATUS_REJECTED
                    && (auth()->user()?->can(Perm::APPLICATIONS_REFUSE) ?? false))
                ->action(function () use ($record) {
                    $record->withoutActivityLog(fn () => $record->update([
                        'status' => MemberApplication::STATUS_REJECTED,
                        'reviewed_at' => now(),
                        'reviewed_by' => auth()->id(),
                    ]));
                    $record->logActivity('rejected');
                    $payload = DiscordPayloads::applicationRejected($record, auth()->user());
                    PostDiscordWebhook::dispatch($payload['content'], $payload['embed'], $payload['reference'])->afterResponse();
                    Notification::make()->title(__('admin.applications.rejected_msg'))->warning()->send();
                    $this->redirect(MemberApplicationResource::getUrl('index'));
                }),
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Filament\Resources\MemberApplications\MemberApplicationResource;
use App\Models\MemberApplication;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMemberApplication extends Notification
{
    use Queueable;

    public function __construct(public MemberApplication $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $app = $this->application;

        return FilamentNotification::make()
            ->title('New member application')
            ->body($app->name . (($summary = $app->summaryAnswer()) ? ' — ' . (is_array($summary['value']) ? implode(', ', $summary['value']) : $summary['value']) : ''))
            ->icon('heroicon-o-user-plus')
            ->iconColor('primary')
            ->actions([
                Action::make('view')
                    ->label('Open application')
                    ->url(MemberApplicationResource::getUrl('edit', ['record' => $app->id]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}

<?php

namespace App\Filament\Resources\Cms\Events\Pages;

use App\Filament\Resources\Cms\Events\EventResource;
use App\Models\Cms\Event;
use App\Support\DiscordDelivery;
use App\Support\DiscordPayloads;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function afterCreate(): void
    {
        /** @var Event $record */
        $record = $this->record;
        DiscordDelivery::toChannel(DiscordPayloads::newEvent($record));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

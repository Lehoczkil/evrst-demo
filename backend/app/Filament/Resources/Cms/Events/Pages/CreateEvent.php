<?php

namespace App\Filament\Resources\Cms\Events\Pages;

use App\Filament\Resources\Cms\Events\EventResource;
use App\Jobs\PostDiscordWebhook;
use App\Models\Cms\Event;
use App\Support\DiscordPayloads;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function afterCreate(): void
    {
        /** @var Event $record */
        $record = $this->record;
        $p = DiscordPayloads::newEvent($record);
        PostDiscordWebhook::dispatch($p['content'], $p['embed'], $p['reference'])->afterResponse();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

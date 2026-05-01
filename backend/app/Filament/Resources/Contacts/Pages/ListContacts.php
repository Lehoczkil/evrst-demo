<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string { return __('admin.resources.contact.p'); }

    public function getHeading(): string { return __('admin.resources.contact.p'); }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.contacts.create_new')),
        ];
    }
}

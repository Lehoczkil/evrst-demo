<?php

namespace App\Filament\Resources\ContactGroups\Pages;

use App\Filament\Resources\ContactGroups\ContactGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContactGroups extends ListRecords
{
    protected static string $resource = ContactGroupResource::class;

    public function getTitle(): string { return __('admin.resources.contact_group.p'); }

    public function getHeading(): string { return __('admin.resources.contact_group.p'); }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.contacts.create_group')),
        ];
    }
}

<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string { return __('admin.contacts.create_new'); }

    public function getHeading(): string { return __('admin.contacts.create_new'); }
}

<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Auth\Perm;
use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string { return __('admin.resources.contact.s'); }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can(Perm::CONTACTS_DELETE) ?? false),
        ];
    }
}

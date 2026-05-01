<?php

namespace App\Filament\Resources\ContactGroups\Pages;

use App\Auth\Perm;
use App\Filament\Resources\ContactGroups\ContactGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContactGroup extends EditRecord
{
    protected static string $resource = ContactGroupResource::class;

    public function getTitle(): string { return __('admin.resources.contact_group.s'); }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can(Perm::CONTACTS_DELETE) ?? false),
        ];
    }
}

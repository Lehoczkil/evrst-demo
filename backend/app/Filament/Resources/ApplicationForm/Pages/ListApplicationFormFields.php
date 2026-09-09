<?php

namespace App\Filament\Resources\ApplicationForm\Pages;

use App\Filament\Resources\ApplicationForm\ApplicationFormFieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApplicationFormFields extends ListRecords
{
    protected static string $resource = ApplicationFormFieldResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.application_form.list_sub');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

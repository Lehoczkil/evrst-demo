<?php

namespace App\Filament\Resources\ApplicationFormSections\Pages;

use App\Filament\Resources\ApplicationFormSections\ApplicationFormSectionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageApplicationFormSections extends ManageRecords
{
    protected static string $resource = ApplicationFormSectionResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.application_form.sections_sub');
    }
}

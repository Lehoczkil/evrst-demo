<?php

namespace App\Filament\Resources\ApplicationForm\Pages;

use App\Filament\Resources\ApplicationForm\ApplicationFormFieldResource;
use App\Models\ApplicationFormField;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApplicationFormField extends EditRecord
{
    protected static string $resource = ApplicationFormFieldResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ApplicationFormField $record */
        $record = $this->record;

        foreach (['label', 'help', 'placeholder'] as $key) {
            $data[$key . '_en'] = $record->{$key}['en'] ?? null;
            $data[$key . '_hu'] = $record->{$key}['hu'] ?? null;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = CreateApplicationFormField::packTranslations($data);

        /** @var ApplicationFormField $record */
        $record = $this->record;

        // The form disables these for a system field, but a disabled input
        // is only a UI affordance — the payload still arrives.
        if ($record->is_system) {
            unset($data['key'], $data['type'], $data['is_active']);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (ApplicationFormField $record) => ! $record->is_system),
        ];
    }
}

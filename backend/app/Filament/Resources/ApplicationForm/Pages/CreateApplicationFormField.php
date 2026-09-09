<?php

namespace App\Filament\Resources\ApplicationForm\Pages;

use App\Filament\Resources\ApplicationForm\ApplicationFormFieldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApplicationFormField extends CreateRecord
{
    protected static string $resource = ApplicationFormFieldResource::class;

    /**
     * label / help / placeholder are single {en, hu} JSON columns, split
     * into two inputs each on the form — the same shape the team member
     * degree fields use.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return self::packTranslations($data);
    }

    /** @return array<string, mixed> */
    public static function packTranslations(array $data): array
    {
        foreach (['label', 'help', 'placeholder'] as $key) {
            $packed = [];

            foreach (['en', 'hu'] as $lang) {
                $value = $data[$key . '_' . $lang] ?? null;
                if (filled($value)) {
                    $packed[$lang] = $value;
                }
                unset($data[$key . '_' . $lang]);
            }

            $data[$key] = $packed === [] ? null : $packed;
        }

        // An option row left blank in the repeater must not become a choice
        // with an empty value — it would render as a blank pill.
        $data['options'] = collect($data['options'] ?? [])
            ->filter(fn ($o) => filled($o['value'] ?? null))
            ->values()
            ->all() ?: null;

        return $data;
    }
}

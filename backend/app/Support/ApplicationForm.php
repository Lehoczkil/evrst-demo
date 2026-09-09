<?php

namespace App\Support;

use App\Models\ApplicationFormField;
use App\Models\ApplicationFormSection;
use App\Models\TeamMemberGroup;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reads the editable join-us form: what to render, what to accept, and how
 * to store what comes back.
 *
 * One source of truth for the three things that used to be written out by
 * hand in three places — the SPA's markup, the controller's rule set, and
 * the admin's read-only view of a submission.
 */
final class ApplicationForm
{
    /**
     * The two keys that are columns on member_applications rather than
     * answers, because the accept flow and every notification need them.
     */
    public const COLUMN_KEYS = ['name', 'email'];

    /**
     * Fields to render and validate, in form order.
     *
     * A system field is included even if someone managed to deactivate it —
     * an application with no name or address cannot be acted on.
     *
     * @return Collection<int, ApplicationFormField>
     */
    public static function fields(): Collection
    {
        return ApplicationFormField::query()
            ->where(fn ($q) => $q->where('is_active', true)->orWhere('is_system', true))
            ->orderBy('position')
            ->get();
    }

    /**
     * The form as the SPA renders it: active sections, each with its fields.
     *
     * @return array<string, mixed>
     */
    public static function schema(?string $lang = null): array
    {
        $fields = self::fields()->groupBy('section_id');

        $sections = ApplicationFormSection::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->map(fn (ApplicationFormSection $section) => [
                'key' => $section->key,
                'title' => TeamMemberGroup::pickLocale($section->title, $lang) ?: $section->key,
                'description' => TeamMemberGroup::pickLocale($section->description, $lang),
                'fields' => collect($fields->get($section->id, []))
                    ->map(fn (ApplicationFormField $field) => [
                        'key' => $field->key,
                        'type' => $field->type,
                        'label' => TeamMemberGroup::pickLocale($field->label, $lang) ?: $field->key,
                        'help' => TeamMemberGroup::pickLocale($field->help, $lang),
                        'placeholder' => TeamMemberGroup::pickLocale($field->placeholder, $lang),
                        'required' => $field->is_required,
                        'maxLength' => $field->maxLength(),
                        'options' => $field->isChoice() ? $field->optionsForLocale($lang) : [],
                    ])
                    ->values()
                    ->all(),
            ])
            // A section whose every field was moved or deactivated would
            // render as an empty numbered card.
            ->filter(fn (array $section) => $section['fields'] !== [])
            ->values()
            ->all();

        return ['sections' => $sections];
    }

    /**
     * The field a list view should summarise a submission by: the first
     * choice question on the form. Today that is the department picker.
     */
    public static function summaryField(): ?ApplicationFormField
    {
        return ApplicationFormField::query()
            ->where('is_system', false)
            ->where('is_active', true)
            ->whereIn('type', ApplicationFormField::CHOICE_TYPES)
            ->orderBy('position')
            ->first();
    }

    /**
     * Validation rules generated from the same fields the SPA rendered.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (self::fields() as $field) {
            $rules += $field->validationRules();
        }

        // Belt and braces: whatever the form says, these two are what the
        // rest of the application flow is built on.
        $rules['name'] = ['required', 'string', 'max:255'];
        $rules['email'] = ['required', 'email', 'string', 'max:255'];

        return $rules;
    }

    /**
     * Split validated input into the columns and the answers map.
     *
     * Keys with no value are dropped rather than stored as null, so an
     * answers map only ever lists questions that were actually answered.
     *
     * @param  array<string, mixed>  $validated
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function split(array $validated): array
    {
        $columns = [];
        $answers = [];

        foreach ($validated as $key => $value) {
            if (in_array($key, self::COLUMN_KEYS, true)) {
                $columns[$key] = $value;

                continue;
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $answers[$key] = $value;
        }

        return [$columns, $answers];
    }
}

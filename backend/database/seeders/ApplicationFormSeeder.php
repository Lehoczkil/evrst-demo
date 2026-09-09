<?php

namespace Database\Seeders;

use App\Models\ApplicationFormField;
use App\Models\ApplicationFormSection;
use Illuminate\Database\Seeder;

/**
 * The join-us form as it was hardcoded in JoinUsPage.vue, field for field.
 *
 * Seeded 1:1 on purpose: turning the form into data must not change what an
 * applicant sees on the day it ships. Everything here — including the
 * department list that had drifted away from the real team_member_groups —
 * is now editable under Membership → Application form.
 *
 * Idempotent (updateOrCreate on the key), so a re-seed refreshes the
 * wording without duplicating questions or orphaning answers.
 */
class ApplicationFormSeeder extends Seeder
{
    private const SECTIONS = [
        ['key' => 'about',        'title' => ['en' => 'About you',                'hu' => 'Rólad']],
        ['key' => 'availability', 'title' => ['en' => 'Availability',             'hu' => 'Elérhetőség']],
        ['key' => 'contribution', 'title' => ['en' => 'Department & contribution', 'hu' => 'Részleg és hozzájárulás']],
    ];

    private const FIELDS = [
        [
            'section' => 'about',
            'key' => 'email',
            'type' => ApplicationFormField::TYPE_EMAIL,
            'label' => ['en' => 'E-mail', 'hu' => 'E-mail'],
            'is_required' => true,
            'is_system' => true,
        ],
        [
            'section' => 'about',
            'key' => 'name',
            'type' => ApplicationFormField::TYPE_TEXT,
            'label' => ['en' => 'Name', 'hu' => 'Név'],
            'is_required' => true,
            'is_system' => true,
        ],
        [
            'section' => 'about',
            'key' => 'university',
            'type' => ApplicationFormField::TYPE_TEXT,
            'label' => ['en' => 'University', 'hu' => 'Egyetem'],
            'is_required' => true,
        ],
        [
            'section' => 'about',
            'key' => 'education',
            'type' => ApplicationFormField::TYPE_RADIO,
            'label' => [
                'en' => 'Current or highest level of education completed',
                'hu' => 'Jelenlegi vagy legmagasabb iskolai végzettség',
            ],
            'options' => [
                ['value' => 'BSc', 'label' => ['en' => 'BSc', 'hu' => 'BSc']],
                ['value' => 'MSc', 'label' => ['en' => 'MSc', 'hu' => 'MSc']],
                ['value' => 'PhD', 'label' => ['en' => 'PhD', 'hu' => 'PhD']],
            ],
        ],
        [
            'section' => 'about',
            'key' => 'faculty',
            'type' => ApplicationFormField::TYPE_TEXT,
            'label' => ['en' => 'Faculty', 'hu' => 'Kar'],
            'is_required' => true,
        ],
        [
            'section' => 'availability',
            'key' => 'why',
            'type' => ApplicationFormField::TYPE_TEXTAREA,
            'label' => ['en' => 'Why do you want to join us?', 'hu' => 'Miért szeretnél csatlakozni hozzánk?'],
            'is_required' => true,
        ],
        [
            'section' => 'availability',
            'key' => 'hours',
            'type' => ApplicationFormField::TYPE_TEXT,
            'label' => [
                'en' => 'How many hours per week can you dedicate to team tasks?',
                'hu' => 'Hány órát tudsz hetente a csapatra fordítani?',
            ],
            'help' => [
                'en' => 'In addition, there will be a 20-minute weekly meeting.',
                'hu' => 'Ezen felül lesz egy 20 perces heti megbeszélés.',
            ],
            'is_required' => true,
            'max_length' => 64,
        ],
        [
            'section' => 'availability',
            'key' => 'languages',
            'type' => ApplicationFormField::TYPE_CHECKBOX,
            'label' => [
                'en' => 'Spoken languages (conversational level)',
                'hu' => 'Beszélt nyelvek (társalgási szinten)',
            ],
            'options' => [
                ['value' => 'Hungarian', 'label' => ['en' => 'Hungarian', 'hu' => 'Magyar']],
                ['value' => 'English',   'label' => ['en' => 'English',   'hu' => 'Angol']],
                ['value' => 'German',    'label' => ['en' => 'German',    'hu' => 'Német']],
            ],
        ],
        [
            'section' => 'availability',
            'key' => 'otherLanguage',
            'type' => ApplicationFormField::TYPE_TEXT,
            'label' => ['en' => 'Other', 'hu' => 'Egyéb'],
            'placeholder' => ['en' => 'Specify other language', 'hu' => 'Add meg a nyelvet'],
            'max_length' => 120,
        ],
        [
            'section' => 'contribution',
            'key' => 'department',
            'type' => ApplicationFormField::TYPE_RADIO,
            'label' => [
                'en' => 'Which department would you like to join?',
                'hu' => 'Melyik részleghez csatlakoznál?',
            ],
            // Seeded exactly as the SPA had them. They no longer match the
            // roster's nine team_member_groups — Electronics and Software are
            // separate groups, and Legal / Web developer are missing — but
            // fixing that is now an edit in the panel, not a deploy.
            'options' => [
                ['value' => 'Marketing & Design', 'label' => ['en' => 'Marketing & Design', 'hu' => 'Marketing & Dizájn']],
                ['value' => 'Electronics & Software Development', 'label' => ['en' => 'Electronics & Software Development', 'hu' => 'Elektronika & Szoftverfejlesztés']],
                ['value' => 'Propulsion', 'label' => ['en' => 'Propulsion', 'hu' => 'Hajtómű']],
                ['value' => 'Structure & Aerodynamics', 'label' => ['en' => 'Structure & Aerodynamics', 'hu' => 'Váz & Aerodinamika']],
                ['value' => 'Management', 'label' => ['en' => 'Management', 'hu' => 'Menedzsment']],
            ],
        ],
        [
            'section' => 'contribution',
            'key' => 'tasks',
            'type' => ApplicationFormField::TYPE_TEXTAREA,
            'label' => [
                'en' => 'What specific tasks or responsibilities would you be interested in?',
                'hu' => 'Milyen konkrét feladatok érdekelnének?',
            ],
            'is_required' => true,
        ],
        [
            'section' => 'contribution',
            'key' => 'skills',
            'type' => ApplicationFormField::TYPE_TEXTAREA,
            'label' => [
                'en' => 'What skills or competencies could be useful to the team?',
                'hu' => 'Milyen készségek vagy kompetenciák lehetnek hasznosak a csapatnak?',
            ],
            'is_required' => true,
        ],
    ];

    public function run(): void
    {
        $sections = [];

        foreach (self::SECTIONS as $position => $entry) {
            $sections[$entry['key']] = ApplicationFormSection::updateOrCreate(
                ['key' => $entry['key']],
                [
                    'title' => $entry['title'],
                    'position' => $position,
                    'is_active' => true,
                ],
            );
        }

        foreach (self::FIELDS as $position => $entry) {
            ApplicationFormField::updateOrCreate(
                ['key' => $entry['key']],
                [
                    'section_id' => $sections[$entry['section']]->id,
                    'type' => $entry['type'],
                    'label' => $entry['label'],
                    'help' => $entry['help'] ?? null,
                    'placeholder' => $entry['placeholder'] ?? null,
                    'options' => $entry['options'] ?? null,
                    'is_required' => $entry['is_required'] ?? false,
                    'is_system' => $entry['is_system'] ?? false,
                    'max_length' => $entry['max_length'] ?? null,
                    'position' => $position,
                    'is_active' => true,
                ],
            );
        }
    }
}

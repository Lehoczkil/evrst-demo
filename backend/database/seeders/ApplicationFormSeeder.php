<?php

namespace Database\Seeders;

use App\Models\ApplicationFormField;
use App\Models\ApplicationFormSection;
use Illuminate\Database\Seeder;

/**
 * The join-us form, ported from the team's Google Form
 * ("E.V.R.S.T. Tagfelvétel"), question for question.
 *
 * The Hungarian wording is the Google Form's own; the English is a
 * translation, because this form is bilingual and the Google one was not.
 *
 * Three deliberate departures from the source, all flippable in the panel:
 *
 *  · "Mennyi időt tudsz…" was a CHECKBOX on the Google Form, so an
 *    applicant could tick 5 and 9 hours at once. It is a single choice.
 *  · "Beszélt idegen nyelv" was a RADIO, so a person speaking English and
 *    German could only say one. It is multi-select.
 *  · Google's "Egyéb" rows are an option plus a free-text box. This form
 *    has no such control, so "Egyéb" is a plain option and the detail
 *    belongs in the open questions that follow.
 *
 * The last question ("Milyen tevékenységben veszel részt?") is conditional
 * on the Google Form — a second page shown only after a Yes. There is no
 * branching here, so it is always visible and optional, with help text
 * saying when to fill it in.
 *
 * Idempotent (updateOrCreate on the key), so a re-seed refreshes the
 * wording without duplicating questions or orphaning answers.
 */
class ApplicationFormSeeder extends Seeder
{
    private const SECTIONS = [
        ['key' => 'about',        'title' => ['en' => 'About you',                 'hu' => 'Rólad']],
        ['key' => 'availability', 'title' => ['en' => 'Motivation & availability', 'hu' => 'Motiváció és ráfordítás']],
        ['key' => 'contribution', 'title' => ['en' => 'Department & experience',   'hu' => 'Részleg és tapasztalat']],
    ];

    /**
     * Keys that were on the previous version of this form and are not on
     * this one. Deactivated rather than deleted: an answer already
     * collected under one of them stays readable on the application it
     * belongs to.
     */
    public const RETIRED_KEYS = ['faculty', 'otherLanguage'];

    private const FIELDS = [
        // ---- Rólad -----------------------------------------------------
        [
            'section' => 'about',
            'key' => 'email',
            'type' => ApplicationFormField::TYPE_EMAIL,
            'label' => ['en' => 'E-mail', 'hu' => 'E-mail'],
            'help' => ['en' => 'Your e-mail address.', 'hu' => 'Az e-mail-címed.'],
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
            'key' => 'source',
            'type' => ApplicationFormField::TYPE_TEXT,
            'label' => ['en' => 'How did you hear about us?', 'hu' => 'Honnan hallottál a csapatunkról?'],
            'is_required' => true,
        ],
        [
            'section' => 'about',
            'key' => 'university',
            'type' => ApplicationFormField::TYPE_TEXT,
            'label' => [
                'en' => 'Institution where you study',
                'hu' => 'Intézmény, ahol a tanulmányaidat végzed',
            ],
            'is_required' => true,
        ],
        [
            'section' => 'about',
            'key' => 'education',
            'type' => ApplicationFormField::TYPE_RADIO,
            'label' => [
                'en' => 'Your current (highest) level of study',
                'hu' => 'Jelenlegi képzési szinted (legmagasabb)',
            ],
            'is_required' => true,
            'options' => [
                ['value' => 'BSc', 'label' => ['en' => 'BSc', 'hu' => 'BSc']],
                ['value' => 'MSc', 'label' => ['en' => 'MSc', 'hu' => 'MSc']],
                ['value' => 'PhD', 'label' => ['en' => 'PhD', 'hu' => 'PhD']],
                ['value' => 'Other', 'label' => ['en' => 'Other', 'hu' => 'Egyéb']],
            ],
        ],
        [
            // Not the old `faculty` key: that asked for a faculty, this asks
            // for the programme and any prior qualification. A key is what
            // every collected answer is filed under, so a changed question
            // gets a new one.
            'section' => 'about',
            'key' => 'programme',
            'type' => ApplicationFormField::TYPE_TEXTAREA,
            'label' => ['en' => 'Your programme', 'hu' => 'Képzésed'],
            'help' => [
                'en' => 'Your major, plus any earlier qualification — a technician certificate, or your BSc if you are on an MSc now.',
                'hu' => 'A szakod, és ha van előképzettséged, azt is írd le (pl. technikusi, vagy ha MSc-n vagy, akkor a BSc képzésedet).',
            ],
            'is_required' => true,
        ],

        // ---- Motiváció és ráfordítás -----------------------------------
        [
            'section' => 'availability',
            'key' => 'why',
            'type' => ApplicationFormField::TYPE_TEXTAREA,
            'label' => [
                'en' => 'Why do you want to work with our team?',
                'hu' => 'Miért szeretnél a csapatunkban dolgozni?',
            ],
            'is_required' => true,
        ],
        [
            'section' => 'availability',
            'key' => 'hours',
            'type' => ApplicationFormField::TYPE_RADIO,
            'label' => [
                'en' => 'How many hours a week can you spend on team work?',
                'hu' => 'Mennyi időt (óra) tudsz a csapatmunkával tölteni egy héten?',
            ],
            'help' => [
                'en' => 'In addition, there will be a 20-minute weekly meeting.',
                'hu' => 'Ezen felül lesz egy 20 perces heti megbeszélés.',
            ],
            'is_required' => true,
            'options' => [
                ['value' => '5', 'label' => ['en' => '5 hours', 'hu' => '5 óra']],
                ['value' => '6', 'label' => ['en' => '6 hours', 'hu' => '6 óra']],
                ['value' => '7', 'label' => ['en' => '7 hours', 'hu' => '7 óra']],
                ['value' => '8', 'label' => ['en' => '8 hours', 'hu' => '8 óra']],
                ['value' => '9', 'label' => ['en' => '9 hours', 'hu' => '9 óra']],
                ['value' => '10', 'label' => ['en' => '10 hours', 'hu' => '10 óra']],
                ['value' => '10+', 'label' => ['en' => '10+ hours', 'hu' => '10+ óra']],
                ['value' => 'Other', 'label' => ['en' => 'Other', 'hu' => 'Egyéb']],
            ],
        ],
        [
            'section' => 'availability',
            'key' => 'languages',
            'type' => ApplicationFormField::TYPE_CHECKBOX,
            'label' => [
                'en' => 'Spoken foreign languages (conversational level)',
                'hu' => 'Beszélt idegen nyelv (társalgási szinten)',
            ],
            'is_required' => true,
            'options' => [
                ['value' => 'None', 'label' => ['en' => 'None', 'hu' => 'Nincs']],
                ['value' => 'English', 'label' => ['en' => 'English', 'hu' => 'Angol']],
                ['value' => 'German', 'label' => ['en' => 'German', 'hu' => 'Német']],
                ['value' => 'Other', 'label' => ['en' => 'Other', 'hu' => 'Egyéb']],
            ],
        ],

        // ---- Részleg és tapasztalat ------------------------------------
        [
            'section' => 'contribution',
            'key' => 'department',
            'type' => ApplicationFormField::TYPE_CHECKBOX,
            'label' => [
                'en' => 'Which department would you like to join?',
                'hu' => 'Melyik részlegben szeretnél elhelyezkedni?',
            ],
            'help' => [
                'en' => 'Other: if there is something very specific you want to work on.',
                'hu' => 'Egyéb: ha nagyon specifikusan szeretnél foglalkozni valamivel.',
            ],
            'is_required' => true,
            'options' => [
                ['value' => 'Marketing-Design', 'label' => ['en' => 'Marketing & Design', 'hu' => 'Marketing-Dizájn']],
                ['value' => 'Electronics', 'label' => ['en' => 'Electronics', 'hu' => 'Elektronika']],
                ['value' => 'Software', 'label' => ['en' => 'Software development', 'hu' => 'Szoftver fejlesztés']],
                ['value' => 'Propulsion', 'label' => ['en' => 'Propulsion', 'hu' => 'Hajtómű']],
                ['value' => 'Structures', 'label' => ['en' => 'Structures & aerodynamics', 'hu' => 'Váz-Aerodinamika']],
                ['value' => 'Business-Communication', 'label' => ['en' => 'Business & communication', 'hu' => 'Gazdasági-Kommunikáció']],
                ['value' => 'ProjectLead', 'label' => ['en' => 'As a project lead', 'hu' => 'Projektvezetőként']],
                ['value' => 'Member', 'label' => ['en' => 'As a member', 'hu' => 'Tagként']],
                ['value' => 'Other', 'label' => ['en' => 'Other', 'hu' => 'Egyéb']],
            ],
        ],
        [
            'section' => 'contribution',
            'key' => 'tasks',
            'type' => ApplicationFormField::TYPE_TEXTAREA,
            'label' => [
                'en' => 'Within that area, what would you like to work on?',
                'hu' => 'A választott területen belül mivel foglalkoznál szívesen?',
            ],
            'help' => [
                'en' => 'In your own words, please.',
                'hu' => 'Kérlek, saját szavaiddal fogalmazd meg.',
            ],
            'is_required' => true,
        ],
        [
            'section' => 'contribution',
            'key' => 'skills',
            'type' => ApplicationFormField::TYPE_TEXTAREA,
            'label' => [
                'en' => 'What skills and experience do you have that could be useful to the team?',
                'hu' => 'Milyen kompetenciákkal, eddigi tapasztalatokkal rendelkezel, amik hasznosak lehetnek a csapatnak?',
            ],
            'is_required' => true,
        ],
        [
            'section' => 'contribution',
            'key' => 'working',
            'type' => ApplicationFormField::TYPE_RADIO,
            'label' => ['en' => 'Are you currently working?', 'hu' => 'Jelenleg dolgozol-e?'],
            'is_required' => true,
            'options' => [
                [
                    'value' => 'YesRelated',
                    'label' => [
                        'en' => 'Yes, and it matches the area I chose',
                        'hu' => 'Igen, és egybevág a választott területemmel',
                    ],
                ],
                [
                    'value' => 'YesUnrelated',
                    'label' => [
                        'en' => 'Yes, but it is unrelated to the area I chose',
                        'hu' => 'Igen, de nem kapcsolódik a választott területemhez',
                    ],
                ],
                ['value' => 'No', 'label' => ['en' => 'No', 'hu' => 'Nem']],
            ],
        ],
        [
            'section' => 'contribution',
            'key' => 'research',
            'type' => ApplicationFormField::TYPE_RADIO,
            'label' => [
                'en' => 'Would you like to write a thesis, a TDK paper or do other research in your section?',
                'hu' => 'Szeretnél-e Szakdolgozatot, TDK-t vagy más kutatást folytatni a szekciódban?',
            ],
            'is_required' => true,
            'options' => [
                ['value' => 'Yes', 'label' => ['en' => 'Yes', 'hu' => 'Igen']],
                ['value' => 'No', 'label' => ['en' => 'No', 'hu' => 'Nem']],
                ['value' => 'Maybe', 'label' => ['en' => 'Maybe', 'hu' => 'Talán']],
            ],
        ],
        [
            'section' => 'contribution',
            'key' => 'extracurricular',
            'type' => ApplicationFormField::TYPE_RADIO,
            'label' => [
                'en' => 'Do you take part in any other extracurricular activity?',
                'hu' => 'Részt veszel-e valamilyen más órákon kívüli tevékenységben?',
            ],
            'help' => [
                'en' => 'For example a student college (szakkollégium).',
                'hu' => 'Pl.: Szakkollégiumban.',
            ],
            'is_required' => true,
            'options' => [
                ['value' => 'Yes', 'label' => ['en' => 'Yes', 'hu' => 'Igen']],
                ['value' => 'No', 'label' => ['en' => 'No', 'hu' => 'Nem']],
            ],
        ],
        [
            // Conditional on the Google Form (a second page after a Yes).
            // No branching here, so: always shown, never required.
            'section' => 'contribution',
            'key' => 'extracurricularDetail',
            'type' => ApplicationFormField::TYPE_TEXTAREA,
            'label' => ['en' => 'What activity do you take part in?', 'hu' => 'Milyen tevékenységben veszel részt?'],
            'help' => [
                'en' => 'Only if you answered Yes above.',
                'hu' => 'Csak akkor töltsd ki, ha az előző kérdésre Igen a válaszod.',
            ],
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

        // Questions this form no longer asks. Hidden, not removed — see
        // RETIRED_KEYS.
        ApplicationFormField::whereIn('key', self::RETIRED_KEYS)
            ->update(['is_active' => false]);
    }
}

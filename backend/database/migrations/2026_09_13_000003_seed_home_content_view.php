<?php

use App\Models\Collection;
use App\Models\Resource as ResourceModel;
use Illuminate\Database\Migrations\Migration;

/**
 * Seed the home-page copy row with what the SPA currently ships.
 *
 * Without this the new "Home texts" screen opens empty. The site would
 * still be correct — every string falls back to the bundled copy — but an
 * admin would face a blank form and have to retype the live text before
 * they could change one word of it.
 *
 * Only creates. If the row already exists it is left alone, so re-running
 * cannot overwrite copy someone has edited in the panel.
 *
 * Creates the `views` collection first if it is missing. Migrations run
 * before seeders, so on a fresh database — every test run — the parent row
 * this one's foreign key points at does not exist yet.
 */
return new class extends Migration
{
    private const ID = 'c1d0e9a4-6f3b-4a21-9b7e-2f5a8c0d4e11';

    private const VIEWS_COLLECTION = 'f2a4ad4c-f5b8-4d7f-9c2c-9d4d6c0b3aaa';

    public function up(): void
    {
        if (ResourceModel::whereKey(self::ID)->exists()) {
            return;
        }

        Collection::firstOrCreate(
            ['id' => self::VIEWS_COLLECTION],
            [
                'name' => 'Views',
                'slug' => 'views',
                'description' => 'Long-form MDX content blocks (e.g. About body).',
            ],
        );

        ResourceModel::create([
            'id' => self::ID,
            'collection_id' => self::VIEWS_COLLECTION,
            'position' => 0,
            'payload' => [
                'name' => 'home-copy',
                'hero' => [
                    'eyebrow' => [
                        'en' => 'Óbuda University · Budapest · Founded 2024',
                        'hu' => 'Óbudai Egyetem · Budapest · Alapítva 2024',
                    ],
                    'title1' => ['en' => 'Escape Velocity', 'hu' => 'Escape Velocity'],
                    'title2' => ['en' => 'Rocketry', 'hu' => 'Rocketry'],
                    'lede' => [
                        'en' => 'We design, build and launch experimental rockets — from CAD to the test stand to the pad.',
                        'hu' => 'Kísérleti rakétákat tervezünk, építünk és indítunk — a CAD-tól a próbapadig és a kilövésig.',
                    ],
                    'ctaJoin' => ['en' => 'Join us', 'hu' => 'Csatlakozz'],
                    'ctaMission' => ['en' => 'Our mission', 'hu' => 'Küldetésünk'],
                    'says' => [
                        [
                            'en' => 'A rocket does not care about good intentions. Only about what <em>works</em>.',
                            'hu' => 'Egy rakétát nem érdekel a jó szándék. Csak az, ami <em>működik</em>.',
                        ],
                        [
                            'en' => 'From CAD to the test stand. Then to the <em>pad</em>.',
                            'hu' => 'A CAD-tól a próbapadig. Aztán a <em>kilövésig</em>.',
                        ],
                        [
                            'en' => 'Nine groups, nineteen people, three rockets. One has <em>flown</em>.',
                            'hu' => 'Kilenc csoport, tizenkilenc ember, három rakéta. Egy már <em>repült</em>.',
                        ],
                    ],
                ],
                'rocket' => [
                    'eyebrow' => ['en' => 'Active vehicle', 'hu' => 'Aktív jármű'],
                    'title' => ['en' => 'The rocket', 'hu' => 'A rakéta'],
                    'status' => ['en' => 'In build', 'hu' => 'Építés alatt'],
                    'dimHeight' => '2 400 mm',
                    'dimDiameter' => '⌀ 102',
                    'specs' => [
                        ['label' => ['en' => 'Height', 'hu' => 'Magasság'], 'value' => '2 400', 'unit' => ['en' => 'mm', 'hu' => 'mm']],
                        ['label' => ['en' => 'Diameter', 'hu' => 'Átmérő'], 'value' => '102', 'unit' => ['en' => 'mm', 'hu' => 'mm']],
                        ['label' => ['en' => 'Lift-off mass', 'hu' => 'Felszálló massza'], 'value' => '11.4', 'unit' => ['en' => 'kg', 'hu' => 'kg']],
                        ['label' => ['en' => 'Thrust', 'hu' => 'Tolóerő'], 'value' => '1 320', 'unit' => ['en' => 'N', 'hu' => 'N']],
                        ['label' => ['en' => 'Target apogee', 'hu' => 'Cél csúcsmagasság'], 'value' => '3 000', 'unit' => ['en' => 'm', 'hu' => 'm']],
                        ['label' => ['en' => 'Motor', 'hu' => 'Hajtómű'], 'value' => 'K', 'unit' => ['en' => 'class', 'hu' => 'osztály']],
                    ],
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ResourceModel::whereKey(self::ID)->delete();
    }
};

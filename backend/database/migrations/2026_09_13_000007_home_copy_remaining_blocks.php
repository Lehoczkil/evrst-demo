<?php

use App\Models\Resource as ResourceModel;
use Illuminate\Database\Migrations\Migration;

/**
 * Seed the rest of the home page's copy into the editable row.
 *
 * The hero and the rocket sheet moved first; these are the blocks that
 * were still only in the SPA's message files — the mission paragraph and
 * its three marks, the founding year, the sponsor pitch, the join call to
 * action, and the address in the footer.
 *
 * Copied verbatim from `frontend/src/translations/{en,hu}/index.ts` as it
 * stands, so the page reads exactly the same the moment this lands and the
 * team edits from the live text rather than from a blank form.
 *
 * Merged into whatever is already on the row, and only where a group is
 * missing — re-running cannot overwrite something edited in the panel.
 */
return new class extends Migration
{
    private const ID = 'c1d0e9a4-6f3b-4a21-9b7e-2f5a8c0d4e11';

    private const BLOCKS = [
        'manifesto' => [
            'founded' => '2024',
            'second' => [
                'en' => 'Nine discipline groups, one test stand and three vehicles. One has flown — the next is being built.',
                'hu' => 'Kilenc szakmai csoport, egy próbapad és három rakéta. Egy már repült — a következő épp épül.',
            ],
            'mark1' => ['en' => 'CAD → test stand → launch', 'hu' => 'CAD → próbapad → kilövés'],
            'mark2' => ['en' => 'EuRoC · Spaceport America Cup', 'hu' => 'EuRoC · Spaceport America Cup'],
            'mark3' => ['en' => 'Open documentation', 'hu' => 'Nyílt dokumentáció'],
        ],
        'sponsors' => [
            'pitchTitle' => [
                'en' => 'A student rocketry team does not build itself',
                'hu' => 'Egy hallgatói rakétacsapat nem építkezik magától',
            ],
            'pitchBody' => [
                'en' => 'Material, machine time, a test stand, travel to the competition. In return the logo is on the rocket, in the documentation and everywhere we appear.',
                'hu' => 'Anyag, gépidő, próbapad, utazás a versenyre. Cserébe a rakétán, a dokumentációban és minden megjelenésünkben ott a logója.',
            ],
            'cta' => ['en' => 'Become a sponsor', 'hu' => 'Legyen támogató'],
        ],
        'join' => [
            'eyebrow' => ['en' => 'Applications open', 'hu' => 'Jelentkezés nyitva'],
            'title' => ['en' => 'Join the team', 'hu' => 'Csatlakozz a csapathoz'],
            'lede' => [
                'en' => 'You do not have to be an engineer. A rocket needs marketing, law and web development at least as much as it needs propulsion.',
                'hu' => 'Nem kell mérnöknek lenned. A rakétához legalább annyira kell marketing, jog és webfejlesztés, mint hajtómű.',
            ],
            'cta' => ['en' => 'Apply', 'hu' => 'Jelentkezem'],
            'question' => ['en' => 'I have a question', 'hu' => 'Kérdésem van'],
        ],
        'contact' => [
            'address' => [
                'en' => 'Óbuda University · Bécsi út 96/b, 1034 Budapest',
                'hu' => 'Óbudai Egyetem · Bécsi út 96/b, 1034 Budapest',
            ],
        ],
    ];

    public function up(): void
    {
        $record = ResourceModel::find(self::ID);

        if (! $record) {
            return; // 000003 creates it; nothing to merge into yet.
        }

        $payload = $record->payload ?? [];

        foreach (self::BLOCKS as $group => $values) {
            // Only fill a group that is not there. Anything already edited
            // in the panel stays exactly as the team left it.
            $payload[$group] = array_merge($values, $payload[$group] ?? []);
        }

        $record->payload = $payload;
        $record->save();
    }

    public function down(): void
    {
        $record = ResourceModel::find(self::ID);

        if (! $record) {
            return;
        }

        $payload = $record->payload ?? [];

        foreach (array_keys(self::BLOCKS) as $group) {
            unset($payload[$group]);
        }

        $record->payload = $payload;
        $record->save();
    }
};

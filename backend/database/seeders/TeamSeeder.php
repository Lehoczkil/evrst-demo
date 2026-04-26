<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class TeamSeeder extends Seeder
{
    private const NAMESPACE_UUID = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Positions (team-member-groups). The order here doubles as the
     * default sort order rendered on the frontend org chart.
     */
    private const POSITIONS = [
        'projekt-menedzser' => ['en' => 'Project manager',         'hu' => 'Projekt menedzser'],
        'marketing-dizajn'  => ['en' => 'Marketing & Design',      'hu' => 'Marketing-Dizájn'],
        'elektronika'       => ['en' => 'Electronics',             'hu' => 'Elektronika'],
        'szoftver'          => ['en' => 'Software',                'hu' => 'Szoftver'],
        'hajtomu'           => ['en' => 'Propulsion',              'hu' => 'Hajtómű'],
        'vaz-aerodinamika'  => ['en' => 'Structure & Aerodynamics','hu' => 'Váz-Aerodinamika'],
        'jog'               => ['en' => 'Legal',                   'hu' => 'Jog'],
        'webfejleszto'      => ['en' => 'Web developer',           'hu' => 'Webfejlesztő'],
    ];

    /**
     * Roster from team-members.xlsx. `positions` lists every position the
     * member holds; `main` is the one used for the org chart and is added
     * to `positions` automatically if not already present. Email defaults
     * to a deterministic <slug>@evrst.test stub for all dev rows except
     * the team's real shared address.
     */
    private const MEMBERS = [
        ['name' => 'Bihari Balázs',          'positions' => ['marketing-dizajn', 'vaz-aerodinamika'], 'main' => 'projekt-menedzser'],
        ['name' => 'Kürtösi Simon',          'positions' => ['elektronika'],                          'main' => 'projekt-menedzser'],
        ['name' => 'Kincses Márk',           'positions' => ['marketing-dizajn'],                     'main' => 'marketing-dizajn'],
        ['name' => 'Bába Kíra',              'positions' => ['elektronika'],                          'main' => 'elektronika'],
        ['name' => 'Lázár Ruben',            'positions' => ['elektronika'],                          'main' => 'elektronika'],
        ['name' => 'Kerek Gábor',            'positions' => ['elektronika', 'szoftver'],              'main' => 'elektronika'],
        ['name' => 'Kriston Zoltán',         'positions' => ['elektronika', 'szoftver'],              'main' => 'elektronika'],
        ['name' => 'Horváth Márton Antal',   'positions' => ['elektronika', 'szoftver'],              'main' => 'elektronika'],
        ['name' => 'Bagi Roland',            'positions' => ['elektronika', 'szoftver'],              'main' => 'szoftver'],
        ['name' => 'Nyári György',           'positions' => ['szoftver'],                             'main' => 'szoftver'],
        ['name' => 'Czirják Péter',          'positions' => ['hajtomu', 'vaz-aerodinamika'],          'main' => 'hajtomu'],
        ['name' => 'Laschek Ádám',           'positions' => ['marketing-dizajn', 'hajtomu'],          'main' => 'hajtomu'],
        ['name' => 'Bába Csaba',             'positions' => ['hajtomu'],                              'main' => 'hajtomu'],
        ['name' => 'Tello-Pálfy Sebastián',  'positions' => ['elektronika', 'szoftver'],              'main' => 'vaz-aerodinamika'],
        ['name' => 'Obsitos Péter',          'positions' => ['vaz-aerodinamika'],                     'main' => 'vaz-aerodinamika'],
        ['name' => 'Hernádi Andre Jozsef',   'positions' => ['hajtomu', 'vaz-aerodinamika'],          'main' => 'vaz-aerodinamika'],
        ['name' => 'Mosberger Péter',        'positions' => ['jog'],                                  'main' => 'jog'],
        ['name' => 'Lehocki László',         'positions' => ['webfejleszto'],                         'main' => 'webfejleszto', 'email' => 'evrstrocket@gmail.com'],
    ];

    private function defaultEmail(string $name): string
    {
        $local = Str::ascii($name);
        $local = Str::lower($local);
        $local = preg_replace('/[^a-z0-9]+/', '.', $local);
        $local = trim((string) $local, '.');
        return ($local === '' ? 'member' : $local) . '@evrst.test';
    }

    private function deterministicUuid(string $name): string
    {
        return Uuid::uuid5(self::NAMESPACE_UUID, $name)->toString();
    }

    public function run(): void
    {
        $collections = CollectionSeeder::COLLECTIONS;
        $groupsCollectionId = $collections['team-member-groups']['id'];
        $membersCollectionId = $collections['team-members']['id'];

        // Wipe legacy roster so old English placeholder entries don't linger.
        Resource::where('collection_id', $membersCollectionId)->delete();
        Resource::where('collection_id', $groupsCollectionId)->delete();

        $groupRecords = [];
        $sort = 0;
        foreach (self::POSITIONS as $slug => $names) {
            $groupRecords[$slug] = Resource::updateOrCreate(
                ['id' => $this->deterministicUuid('group-' . $slug)],
                [
                    'collection_id' => $groupsCollectionId,
                    'payload' => ['name' => $names],
                    'position' => $sort++,
                ],
            );
        }

        foreach (self::MEMBERS as $i => $member) {
            $main = $groupRecords[$member['main']];
            $positionSlugs = $member['positions'];
            if (! in_array($member['main'], $positionSlugs, true)) {
                $positionSlugs[] = $member['main'];
            }
            $positions = array_map(function ($slug) use ($groupRecords) {
                $g = $groupRecords[$slug];
                return ['id' => $g->id, 'payload' => $g->payload];
            }, $positionSlugs);

            Resource::updateOrCreate(
                ['id' => $this->deterministicUuid('member-' . $member['name'])],
                [
                    'collection_id' => $membersCollectionId,
                    'payload' => [
                        'name' => $member['name'],
                        'email' => $member['email'] ?? $this->defaultEmail($member['name']),
                        'positions' => array_values($positions),
                        'main_position' => [
                            'id' => $main->id,
                            'payload' => $main->payload,
                        ],
                    ],
                    'position' => $i,
                ],
            );
        }
    }
}

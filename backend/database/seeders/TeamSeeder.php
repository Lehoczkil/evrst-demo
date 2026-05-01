<?php

namespace Database\Seeders;

use App\Auth\Perm;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\TeamMemberGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeamSeeder extends Seeder
{
    /**
     * Positions (team_member_groups). The order here doubles as the
     * default sort order rendered on the frontend org chart.
     */
    private const POSITIONS = [
        'csapat-menedzser'  => ['en' => 'Team manager',            'hu' => 'Csapat menedzser'],
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
     * Roster from névjegyzék.xlsx (2026-05). Each row carries:
     *   positions[]          — every group the member belongs to
     *   main                 — primary org-chart position
     *   discord_nick         — server nickname (display name)
     *   discord_username     — globally-unique Discord handle (e.g. "e.1415")
     *   discord_id           — numeric snowflake; null until collected (only
     *                          snowflakes resolve `<@id>` mentions / can be
     *                          targeted by the future DM bot)
     *   email_private        — personal contact email from the spreadsheet
     *   email                — EVRST account email; defaults to a
     *                          deterministic <slug>@evrst.test stub except
     *                          where overridden (Lehocki keeps the team's
     *                          shared address).
     *   role                 — Perm::ROLE_* assigned to the linked User;
     *                          defaults to ROLE_MEMBER when omitted.
     */
    private const MEMBERS = [
        ['name' => 'Bihari Bertalan',        'positions' => ['csapat-menedzser'],                     'main' => 'csapat-menedzser',
            'role' => Perm::ROLE_ADMIN,
            'discord_username' => 'szwego',           'discord_nick' => 'Berci',            'email_private' => 'biharibertalan@gmail.com'],
        ['name' => 'Klabacsek Bálint',       'positions' => ['csapat-menedzser'],                     'main' => 'csapat-menedzser',
            'role' => Perm::ROLE_ADMIN,
            'discord_username' => 'e.1415',           'discord_nick' => 'Bálint',           'email_private' => 'klabacsekbalint@gmail.com'],
        ['name' => 'Bihari Balázs',          'positions' => ['marketing-dizajn', 'vaz-aerodinamika'], 'main' => 'projekt-menedzser',
            'role' => Perm::ROLE_ADMIN,
            'discord_username' => 's_thedepraved',    'discord_nick' => 'Balázs',           'email_private' => 'balazs.bihari2@gmail.com'],
        ['name' => 'Kincses Márk',           'positions' => ['marketing-dizajn'],                     'main' => 'marketing-dizajn',
            'discord_username' => 'kincsesmark3',     'discord_nick' => 'Márk',             'email_private' => 'kincsesmark3@gmail.com'],
        ['name' => 'Bába Kíra',              'positions' => ['elektronika'],                          'main' => 'elektronika',
            'discord_username' => 'b.kira11',         'discord_nick' => 'Kíra :)',          'email_private' => 'babakira520@gmail.com'],
        ['name' => 'Czirják Péter',          'positions' => ['hajtomu', 'vaz-aerodinamika'],          'main' => 'hajtomu',
            'role' => Perm::ROLE_MANAGER,
            'discord_username' => 'retepeter1',       'discord_nick' => 'RetePeTerminator', 'email_private' => 'peterczirjak1998@gmail.com'],
        ['name' => 'Kürtösi Simon',          'positions' => ['elektronika'],                          'main' => 'projekt-menedzser',
            'role' => Perm::ROLE_ADMIN,
            'discord_username' => 'djfighter',        'discord_nick' => 'Simon',            'email_private' => 'kurtosi.simon@gmail.com'],
        ['name' => 'Lázár Ruben',            'positions' => ['elektronika'],                          'main' => 'elektronika',
            'role' => Perm::ROLE_MANAGER,
            'discord_username' => 'prrruben',         'discord_nick' => 'Ruben',            'email_private' => 'rubenlazar@stud.uni-obuda.hu'],
        ['name' => 'Kerek Gábor',            'positions' => ['elektronika', 'szoftver'],              'main' => 'elektronika',
            'role' => Perm::ROLE_MANAGER,
            'discord_username' => 'gabor0150',        'discord_nick' => 'Gábor',            'email_private' => 'kerek.gabo@gmail.com'],
        ['name' => 'Tello-Pálfy Sebastián',  'positions' => ['hajtomu', 'vaz-aerodinamika'],          'main' => 'vaz-aerodinamika',
            'discord_username' => 'sbstn_264876',     'discord_nick' => 'Sebastian',        'email_private' => 'sebastian.tellopalfy@gmail.com'],
        ['name' => 'Kriston Zoltán',         'positions' => ['elektronika', 'szoftver'],              'main' => 'elektronika',
            'discord_username' => 'k_zoli',           'discord_nick' => 'Zoli',             'email_private' => 'kristonzoli2002@gmail.com'],
        ['name' => 'Horváth Márton Antal',   'positions' => ['elektronika', 'szoftver'],              'main' => 'elektronika',
            'discord_username' => 'duckyducky',       'discord_nick' => 'Marci',            'email_private' => 'marton.horvath302@gmail.com'],
        ['name' => 'Bagi Roland',            'positions' => ['elektronika', 'szoftver'],              'main' => 'szoftver',
            'discord_username' => 'roland6180',       'discord_nick' => 'Róland',           'email_private' => 'roland.bagi007@gmail.com'],
        ['name' => 'Obsitos Péter',          'positions' => ['vaz-aerodinamika'],                     'main' => 'vaz-aerodinamika',
            'discord_username' => 'petter0655',       'discord_nick' => 'Obsitos Peti OP',  'email_private' => 'obsitospeti04@gmail.com'],
        ['name' => 'Laschek Ádám',           'positions' => ['marketing-dizajn', 'hajtomu'],          'main' => 'hajtomu',
            'discord_username' => 'adamlasy',         'discord_nick' => 'Ádám',             'email_private' => 'adam.laschek@gmail.com'],
        ['name' => 'Bába Csaba',             'positions' => ['hajtomu'],                              'main' => 'hajtomu',
            'discord_username' => 'kgbcsabi',         'discord_nick' => 'Csabi',            'email_private' => 'baba.csabi@gmail.com'],
        ['name' => 'Hernádi Andre Jozsef',   'positions' => ['hajtomu', 'vaz-aerodinamika'],          'main' => 'vaz-aerodinamika',
            'discord_username' => 'andre_j3805',      'discord_nick' => 'Andre_J',          'email_private' => 'andrehernadi@gmail.com'],
        ['name' => 'Nyári György',           'positions' => ['szoftver'],                             'main' => 'szoftver',
            'discord_username' => 'gyurka',           'discord_nick' => 'Gyurka',           'email_private' => null],
        ['name' => 'Mosberger Péter',        'positions' => ['jog'],                                  'main' => 'jog',
            'discord_id' => null,               'discord_nick' => null,               'email_private' => 'mosbergerpeti@gmail.com'],
        ['name' => 'Lehocki László',         'positions' => ['webfejleszto'],                         'main' => 'webfejleszto',
            'discord_username' => 'lehoczkilaci',     'discord_nick' => 'lehoczkilaci',     'email_private' => 'lehoczkilaszlo2002@gmail.com',
            'email' => 'evrstrocket@gmail.com'],
        ['name' => 'Som Nemere',             'positions' => ['webfejleszto'],                         'main' => 'webfejleszto',
            'discord_username' => 'somnenie',         'discord_nick' => 'Somnenie',         'email_private' => null],
    ];

    private function defaultEmail(string $name): string
    {
        $local = Str::ascii($name);
        $local = Str::lower($local);
        $local = preg_replace('/[^a-z0-9]+/', '.', $local);
        $local = trim((string) $local, '.');
        return ($local === '' ? 'member' : $local) . '@evrst.test';
    }

    public function run(): void
    {
        // Re-seed idempotently: wipe existing rows so renames in this
        // seeder don't accumulate stale duplicates.
        TeamMember::query()->forceDelete();
        TeamMemberGroup::query()->forceDelete();

        $groups = [];
        $sort = 0;
        foreach (self::POSITIONS as $slug => $names) {
            $groups[$slug] = TeamMemberGroup::create([
                'slug' => $slug,
                'name' => $names,
                'kind' => 'department',
                'position' => $sort++,
            ]);
        }

        $rolesByKey = Role::whereIn('key', [Perm::ROLE_ADMIN, Perm::ROLE_MANAGER, Perm::ROLE_MEMBER])
            ->get()->keyBy('key');

        foreach (self::MEMBERS as $i => $info) {
            $email = $info['email'] ?? $this->defaultEmail($info['name']);
            $roleKey = $info['role'] ?? Perm::ROLE_MEMBER;

            // Account password is the project-wide dev value `password`,
            // and password_changed_at is pre-stamped so the seeded users
            // skip the first-login password change like admin@evrst.test.
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $info['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $rolesByKey[$roleKey]?->id,
                    'password_changed_at' => now(),
                ],
            );

            $member = TeamMember::create([
                'name' => $info['name'],
                'email' => $email,
                'email_private' => $info['email_private'] ?? null,
                'discord_nick' => $info['discord_nick'] ?? null,
                'discord_username' => $info['discord_username'] ?? null,
                'discord_id' => $info['discord_id'] ?? null,
                'user_id' => $user->id,
                'position' => $i,
                'is_public' => true,
                'joined_at' => now()->subYear()->startOfMonth()->toDateString(),
            ]);

            $positionSlugs = $info['positions'];
            if (! in_array($info['main'], $positionSlugs, true)) {
                $positionSlugs[] = $info['main'];
            }

            $pivotData = [];
            foreach ($positionSlugs as $slug) {
                $pivotData[$groups[$slug]->id] = ['is_primary' => false];
            }
            $member->groups()->sync($pivotData);
            $member->setPrimaryGroup($groups[$info['main']]->id);
        }
    }
}

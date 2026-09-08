<?php

namespace Database\Seeders;

use App\Auth\Perm;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\TeamMemberGroup;
use App\Models\User;
use App\Support\OrgEmail;
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
     *   email                — EVRST org address; defaults to the
     *                          <given>.<surname>@evrst.hu form minted by
     *                          App\Support\OrgEmail. Override only for a
     *                          shared/functional mailbox.
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
        ['name' => 'Lehoczki László',        'positions' => ['webfejleszto'],                         'main' => 'webfejleszto',
            // Operates the panel and the deploy — needs Users, Roles and
            // Applications, all of which are gated on isAdmin().
            'role' => Perm::ROLE_ADMIN,
            'discord_username' => 'lehoczkilaci',     'discord_nick' => 'lehoczkilaci',     'email_private' => 'lehoczkilaszlo2002@gmail.com'],
        ['name' => 'Som Nemere',             'positions' => ['webfejleszto'],                         'main' => 'webfejleszto',
            'discord_username' => 'somnenie',         'discord_nick' => 'Somnenie',         'email_private' => null],
    ];

    /**
     * The member's org address — the one they sign in with and, once the
     * mailboxes exist, receive mail at. See App\Support\OrgEmail for the
     * format rules.
     */
    private function orgEmail(string $name): string
    {
        return OrgEmail::forName($name) ?? 'member@' . OrgEmail::domain();
    }

    /**
     * The pre-org `<slug>@evrst.test` stub this roster used to mint. Kept
     * only so a re-seed over an older database matches the existing User
     * row by its old login instead of creating a duplicate account.
     */
    private function legacyStubEmail(string $name): string
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

        // Track every fresh-provisioning so the admin can distribute the
        // temp passwords once at the end (not emailed — that would spam
        // 21 inboxes on every reseed).
        $tempPasswords = [];

        foreach (self::MEMBERS as $i => $info) {
            // The org address is both the team_members row's email on
            // record and the login (users.email). Mail delivery is a
            // separate concern — see User::deliveryEmail(), which keeps
            // sending to email_private until the mailboxes go live.
            $orgEmail = $info['email'] ?? $this->orgEmail($info['name']);
            $roleKey = $info['role'] ?? Perm::ROLE_MEMBER;

            // First seed → mint a unique 16-char temp password and leave
            // password_changed_at null so the RequirePasswordChange
            // middleware forces a reset on first login. Re-runs preserve
            // the existing User row's password (don't lock anyone out) but
            // normalise the login to the org address. Match on every
            // address this account may historically have used — org, the
            // personal one, and the retired @evrst.test stub — so a
            // re-seed relinks instead of minting a duplicate login.
            $candidates = array_values(array_filter(array_unique([
                $orgEmail,
                $info['email_private'] ?? null,
                $this->legacyStubEmail($info['name']),
            ])));
            $existing = User::whereIn('email', $candidates)->first();
            if ($existing) {
                $existing->forceFill([
                    'name' => $info['name'],
                    'email' => $orgEmail,
                    'role_id' => $rolesByKey[$roleKey]?->id,
                ])->save();
                $user = $existing;
            } else {
                $temp = Str::password(16);
                $user = User::create([
                    'email' => $orgEmail,
                    'name' => $info['name'],
                    'password' => Hash::make($temp),
                    'role_id' => $rolesByKey[$roleKey]?->id,
                    'password_changed_at' => null,
                ]);
                $tempPasswords[] = [
                    'name' => $info['name'],
                    'email' => $orgEmail,
                    'role' => $roleKey,
                    'password' => $temp,
                ];
            }

            $member = TeamMember::create([
                'name' => $info['name'],
                'email' => $orgEmail,
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

        $this->reportTempPasswords($tempPasswords);
    }

    /**
     * Print the freshly-minted temp passwords once at the end of seeding
     * so the admin can distribute them out-of-band. Also persisted to
     * `storage/app/seeded-team-passwords.txt` (gitignored under
     * `storage/app/`) for retrieval after the console scrolls past.
     *
     * @param  array<int, array{name: string, email: string, role: string, password: string}>  $rows
     */
    private function reportTempPasswords(array $rows): void
    {
        if ($rows === []) {
            $this->command?->info('TeamSeeder: no new users provisioned (existing rows kept their passwords).');
            return;
        }

        $this->command?->newLine();
        $this->command?->warn('TeamSeeder provisioned ' . count($rows) . ' new login(s). Distribute these out-of-band:');
        $this->command?->table(
            ['Name', 'Email', 'Role', 'Temporary password'],
            array_map(fn ($r) => [$r['name'], $r['email'], $r['role'], $r['password']], $rows),
        );
        $this->command?->info('Each user will be redirected to set a new password on first sign-in at /admin.');

        $path = storage_path('app/seeded-team-passwords.txt');
        $body = "Generated " . now()->toDateTimeString() . PHP_EOL . PHP_EOL;
        foreach ($rows as $r) {
            $body .= sprintf("%-30s %-40s %-10s %s%s", $r['name'], $r['email'], $r['role'], $r['password'], PHP_EOL);
        }
        @file_put_contents($path, $body);
        $this->command?->comment('Also written to ' . $path);
    }
}

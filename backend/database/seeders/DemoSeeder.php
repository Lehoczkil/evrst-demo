<?php

namespace Database\Seeders;

use App\Auth\Perm;
use App\Models\BugReport;
use App\Models\CalendarEvent;
use App\Models\Cms\AboutProject;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\MemberApplication;
use App\Models\OnshapeModel;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskProof;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * Populates every Filament-visible entity with demo-quality data so the
 * panel feels alive after `migrate:fresh --seed`. Owns: contacts, contact
 * groups, member applications, calendar events, bug reports, onshape
 * model placeholders, items + stocks. Extends tasks if TaskSeeder didn't
 * produce enough variety. Does not touch users, roles, team members, or
 * CMS resources — those are owned by earlier seeders.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->wipeOwnedTables();

        $admin = User::where('email', 'admin@evrst.test')->firstOrFail();
        $admins = User::query()
            ->whereHas('role', fn ($q) => $q->where('key', Perm::ROLE_ADMIN))
            ->get();
        $managers = User::query()
            ->whereHas('role', fn ($q) => $q->where('key', Perm::ROLE_MANAGER))
            ->get();
        $members = User::query()
            ->whereHas('role', fn ($q) => $q->where('key', Perm::ROLE_MEMBER))
            ->get();
        $teamMembers = TeamMember::all();

        // Bail loudly if any prerequisite seeder is missing — the cascade
        // below assumes a populated team + role catalog.
        if ($admins->isEmpty() || $managers->isEmpty() || $members->isEmpty() || $teamMembers->isEmpty()) {
            $this->command?->warn('DemoSeeder: skipping — RoleSeeder/TeamSeeder did not produce expected users.');
            return;
        }

        $this->seedMemberApplications($admins, $teamMembers);
        $this->seedCalendarEvents($admins->concat($managers)->concat($members));
        $this->extendTasks($admin, $admins, $managers, $members);
        $this->seedContacts();
        $this->seedBugReports($admins->concat($managers)->concat($members), $admins);
        $this->seedOnshapeModels($admin);
        $this->seedInventory($teamMembers);
    }

    private function wipeOwnedTables(): void
    {
        Contact::query()->forceDelete();
        ContactGroup::query()->forceDelete();
        BugReport::query()->delete();
        CalendarEvent::query()->delete();
        MemberApplication::query()->delete();
        OnshapeModel::query()->delete();
        ItemStock::query()->delete();
        Item::query()->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $admins
     * @param  \Illuminate\Support\Collection<int, TeamMember>  $teamMembers
     */
    private function seedMemberApplications($admins, $teamMembers): void
    {
        $reviewer = $admins->first();
        $acceptedTargets = $teamMembers->whereNotIn('name', ['Bihari Bertalan', 'Klabacsek Bálint'])->take(2)->values();

        $rows = [
            [
                'name' => 'Tóth Eszter',
                'email' => 'toth.eszter@stud.uni-obuda.hu',
                'university' => 'Óbudai Egyetem',
                'education' => 'BSc Mechatronics — 2nd year',
                'faculty' => 'Bánki Donát Gépész és Biztonságtechnikai Mérnöki Kar',
                'why' => "Régóta lelkesedem az űrkutatásért — gimnáziumban a fizika OKTV-n is rakétaaerodinamikával foglalkoztam. Szeretnék gyakorlati mérnöki tapasztalatot szerezni egy valódi projekten, ahol a CAD-tervezéstől a tesztpadig minden saját kézzel készül.",
                'hours' => '10-15 óra / hét',
                'languages' => ['Hungarian', 'English', 'German'],
                'department' => 'vaz-aerodinamika',
                'tasks' => 'Strukturális szimuláció, CFD analízis, kompozit gyártás.',
                'skills' => 'SolidWorks, ANSYS Fluent, alapszintű Python, kompozit kézi laminálás.',
                'status' => MemberApplication::STATUS_PENDING,
                'created_at' => now()->subDays(2),
            ],
            [
                'name' => 'Nagy Bence',
                'email' => 'nagy.bence@example.com',
                'university' => null,
                'education' => null,
                'faculty' => null,
                'why' => 'Érdekel a rakétaépítés.',
                'hours' => '5 óra / hét',
                'languages' => ['Hungarian'],
                'department' => null,
                'tasks' => null,
                'skills' => null,
                'status' => MemberApplication::STATUS_PENDING,
                'created_at' => now()->subDays(11),
            ],
            [
                'name' => 'Kovács Anna',
                'email' => 'kovacs.anna@stud.uni-obuda.hu',
                'university' => 'Óbudai Egyetem',
                'education' => 'BSc Computer Engineering — 3rd year',
                'faculty' => 'Kandó Kálmán Villamosmérnöki Kar',
                'why' => 'I want to contribute to embedded software for the avionics stack and learn about real-time telemetry.',
                'hours' => '8-12 hours / week',
                'languages' => ['English', 'Hungarian'],
                'department' => 'szoftver',
                'tasks' => 'Firmware (STM32), telemetry pipeline, ground station UI.',
                'skills' => 'C/C++, Rust basics, FreeRTOS, Git, KiCad layout.',
                'status' => MemberApplication::STATUS_PENDING,
                'created_at' => now()->subDays(20),
            ],
            [
                'name' => 'Szabó Gergő',
                'email' => 'szabo.gergo@gmail.com',
                'university' => 'BME',
                'education' => 'MSc Aerospace — 1st year',
                'faculty' => 'Közlekedésmérnöki és Járműmérnöki Kar',
                'why' => 'Final-year MSc student looking to apply propulsion theory on a real test stand.',
                'hours' => '15+ hours / week',
                'languages' => ['Hungarian', 'English'],
                'department' => 'hajtomu',
                'tasks' => 'Solid + hybrid motor design, test stand instrumentation.',
                'skills' => 'OpenRocket, Cantera, MATLAB, LabVIEW.',
                'status' => MemberApplication::STATUS_PENDING,
                'created_at' => now()->subHours(8),
            ],
            [
                'name' => 'Fekete Júlia',
                'email' => 'fekete.julia@stud.uni-obuda.hu',
                'university' => 'Óbudai Egyetem',
                'education' => 'BSc Industrial Design — 2nd year',
                'faculty' => 'Rejtő Sándor Könnyűipari és Környezetmérnöki Kar',
                'why' => 'Marketing design + branding background. Want to help with the team identity, social posts, and competition merchandise.',
                'hours' => '6-8 hours / week',
                'languages' => ['Hungarian', 'English'],
                'department' => 'marketing-dizajn',
                'tasks' => 'Brand kit refresh, social media, mission-patch design.',
                'skills' => 'Adobe Illustrator, Figma, photography, light video editing.',
                'status' => MemberApplication::STATUS_ACCEPTED,
                'reviewed_at' => now()->subDays(35),
                'team_member_id' => $acceptedTargets[0]?->id,
                'created_at' => now()->subDays(40),
            ],
            [
                'name' => 'Varga Dániel',
                'email' => 'varga.daniel@gmail.com',
                'university' => 'Óbudai Egyetem',
                'education' => 'BSc EE — 2nd year',
                'faculty' => 'Kandó Kálmán Villamosmérnöki Kar',
                'why' => 'Hobby electronics for years; want to work on the GNC stack.',
                'hours' => '10 hours / week',
                'languages' => ['Hungarian', 'English'],
                'department' => 'elektronika',
                'tasks' => 'IMU integration, sensor fusion, ground link.',
                'skills' => 'Altium, Python, embedded C, soldering.',
                'status' => MemberApplication::STATUS_ACCEPTED,
                'reviewed_at' => now()->subDays(50),
                'team_member_id' => $acceptedTargets[1]?->id,
                'created_at' => now()->subDays(55),
            ],
            [
                'name' => 'Horváth Krisztián',
                'email' => 'horvath.krisztian@example.com',
                'university' => 'ELTE',
                'education' => 'BSc Physics — 1st year',
                'faculty' => 'Természettudományi Kar',
                'why' => 'Crashed two model rockets last summer; ready for something bigger.',
                'hours' => '3-4 hours / week',
                'languages' => ['Hungarian'],
                'department' => null,
                'tasks' => null,
                'skills' => 'Hobby model rocketry; no engineering background yet.',
                'status' => MemberApplication::STATUS_REJECTED,
                'reviewed_at' => now()->subDays(15),
                'created_at' => now()->subDays(18),
            ],
        ];

        foreach ($rows as $row) {
            $createdAt = $row['created_at'];
            unset($row['created_at']);

            $reviewedAt = $row['reviewed_at'] ?? null;
            $teamMemberId = $row['team_member_id'] ?? null;
            unset($row['reviewed_at'], $row['team_member_id']);

            $row['reviewed_by'] = in_array($row['status'], [MemberApplication::STATUS_ACCEPTED, MemberApplication::STATUS_REJECTED], true)
                ? $reviewer?->id
                : null;
            $row['reviewed_at'] = $reviewedAt;
            $row['team_member_id'] = $teamMemberId;

            $application = new MemberApplication($row);
            $application->status = $row['status'];
            $application->reviewed_at = $reviewedAt;
            $application->reviewed_by = $row['reviewed_by'];
            $application->team_member_id = $teamMemberId;
            $application->created_at = $createdAt;
            $application->updated_at = $reviewedAt ?? $createdAt;

            // Build the row in two passes: bare create (the LogsActivity
            // hook fires "created"), then a withoutActivityLog update so
            // accepted / rejected get a single hand-shaped log entry
            // instead of an "updated" diff dump.
            $application->withoutActivityLog(fn () => $application->save());

            if ($application->status === MemberApplication::STATUS_ACCEPTED) {
                $application->logActivity('accepted', [
                    'team_member' => $teamMemberId
                        ? ['id' => $teamMemberId, 'name' => TeamMember::find($teamMemberId)?->name]
                        : null,
                ]);
            } elseif ($application->status === MemberApplication::STATUS_REJECTED) {
                $application->logActivity('rejected');
            } else {
                $application->logActivity('created');
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $allUsers
     */
    private function seedCalendarEvents($allUsers): void
    {
        $projects = AboutProject::all();

        $events = [
            ['title' => 'Heti standup', 'description' => 'Subteam updates, blockers, week-ahead planning.', 'days' => -7, 'duration_h' => 1, 'color' => '#0ea5e9'],
            ['title' => 'EuRoC felkészülés — review', 'description' => 'Mid-cycle design review for the Helios airframe and recovery.', 'days' => -3, 'duration_h' => 2, 'color' => '#f97316'],
            ['title' => 'Sponsorship pitch — BME', 'description' => 'Onsite pitch to BME industry partners.', 'days' => -1, 'duration_h' => 1, 'color' => '#22c55e'],
            ['title' => 'Aero subteam meeting', 'description' => 'Wind tunnel slot review + CFD outputs.', 'days' => 2, 'duration_h' => 1, 'color' => '#a855f7'],
            ['title' => 'Static fire — Atlas-1', 'description' => 'Test stand fire at the propulsion site. PPE required.', 'days' => 5, 'duration_h' => 4, 'all_day' => false, 'color' => '#dc2626'],
            ['title' => 'Open day — Óbuda', 'description' => 'Demo table at the Óbuda University open day.', 'days' => 12, 'all_day' => true, 'color' => '#0ea5e9'],
            ['title' => 'Avionics integration day', 'description' => 'Bench-fit the new avionics tray + harness.', 'days' => 18, 'duration_h' => 6, 'color' => '#14b8a6'],
            ['title' => 'EuRoC competition kick-off', 'description' => 'Travel briefing + final logistics.', 'days' => 28, 'all_day' => true, 'color' => '#f59e0b'],
        ];

        foreach ($events as $i => $row) {
            $start = $row['days'] >= 0 ? now()->addDays($row['days']) : now()->subDays(abs($row['days']));
            $allDay = $row['all_day'] ?? false;
            $start = $allDay ? $start->copy()->startOfDay() : $start->copy()->setTime(10 + ($i % 6), 0);
            $end = $allDay
                ? $start->copy()->endOfDay()
                : $start->copy()->addHours($row['duration_h'] ?? 1);

            $creator = $allUsers[$i % $allUsers->count()];
            $project = $projects->isNotEmpty() && in_array($i, [1, 4], true)
                ? $projects[$i % $projects->count()]
                : null;

            $event = CalendarEvent::create([
                'user_id' => $creator->id,
                'project_id' => $project?->id,
                'title' => $row['title'],
                'description' => $row['description'],
                'start_at' => $start,
                'end_at' => $end,
                'all_day' => $allDay,
                'color' => $row['color'],
                'location' => $i % 3 === 0 ? 'EVRST lab — Óbuda' : null,
            ]);
            $event->logActivity('created');
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $admins
     * @param  \Illuminate\Support\Collection<int, User>  $managers
     * @param  \Illuminate\Support\Collection<int, User>  $members
     */
    private function extendTasks(User $admin, $admins, $managers, $members): void
    {
        if (Task::query()->count() >= 20) {
            return;
        }

        $supervisors = $admins->concat($managers)->values();
        $assigneePool = $members->concat($managers)->values();

        // (title, description, status, priority, category, dueOffset, supervisorIdx, assigneeIdxs[])
        $extra = [
            ['Avionics PCB v3 layout',          'Route the v3 board with new IMU + barometer.',                   Task::STATUS_IN_PROGRESS, Task::PRIORITY_HIGH,   Task::CATEGORY_HARDWARE, 4],
            ['Flight computer firmware port',   'Migrate the legacy firmware to FreeRTOS.',                       Task::STATUS_TODO,        Task::PRIORITY_HIGH,   Task::CATEGORY_SOFTWARE, 14],
            ['Recovery deployment test',        'Drop test the new dual-deployment system.',                      Task::STATUS_TESTING,     Task::PRIORITY_URGENT, Task::CATEGORY_HARDWARE, -2],
            ['Fin can lay-up',                  'Hand layup of the carbon fiber fin can — round 2.',              Task::STATUS_DONE,        Task::PRIORITY_NORMAL, Task::CATEGORY_HARDWARE, -10],
            ['Update sponsor deck',             'Refresh slides with Q1 milestones for sponsor outreach.',        Task::STATUS_IN_PROGRESS, Task::PRIORITY_NORMAL, Task::CATEGORY_OUTREACH, 7],
            ['Lab cleanup roster',              'Set up a weekly cleanup rotation for the lab.',                  Task::STATUS_TODO,        Task::PRIORITY_LOW,    Task::CATEGORY_ADMIN,    null],
            ['Frontend: rocket viewer polish',  'Add loading state + mobile fallback for the home rocket model.', Task::STATUS_TESTING,     Task::PRIORITY_NORMAL, Task::CATEGORY_WEBPAGE,  3],
            ['Helios CAD model — fuselage',     'Final fuselage CAD with mounting brackets.',                     Task::STATUS_IN_PROGRESS, Task::PRIORITY_HIGH,   Task::CATEGORY_MODEL,    9],
            ['Update safety documentation',     'Add the new test stand procedure to the safety doc.',            Task::STATUS_TODO,        Task::PRIORITY_NORMAL, Task::CATEGORY_DOCS,     20],
            ['Open day demo prep',              'Plan the demo table layout + handouts for the open day.',        Task::STATUS_TODO,        Task::PRIORITY_NORMAL, Task::CATEGORY_OUTREACH, 11],
            ['EuRoC entry form submission',     'Fill + submit the technical reports portion of the entry.',      Task::STATUS_DONE,        Task::PRIORITY_URGENT, Task::CATEGORY_ADMIN,    -25],
            ['Sponsor logo wall update',        'Add the two new sponsors to the home page logo strip.',          Task::STATUS_DONE,        Task::PRIORITY_LOW,    Task::CATEGORY_WEBPAGE,  -7],
            ['Test stand — load cell calibration', 'Re-zero the 50 kgf load cell and document the procedure.',    Task::STATUS_IN_PROGRESS, Task::PRIORITY_HIGH,   Task::CATEGORY_HARDWARE, 1],
            ['Translate admin help copy',       'Pass over the HU translations for the new help modals.',         Task::STATUS_TESTING,     Task::PRIORITY_LOW,    Task::CATEGORY_DOCS,     -1],
            ['Ground station UI mockup',        'Initial Figma mockup of the ground station live telemetry.',     Task::STATUS_TODO,        Task::PRIORITY_NORMAL, Task::CATEGORY_WEBPAGE,  16],
        ];

        $createdTasks = [];
        $position = (int) (Task::max('position') ?? 0) + 1;

        foreach ($extra as $i => [$title, $desc, $status, $priority, $category, $dueOffset]) {
            $supervisor = $supervisors[$i % $supervisors->count()];
            $assigneeCount = 1 + ($i % 3);
            $assignees = $assigneePool->skip($i % max($assigneePool->count() - $assigneeCount, 1))->take($assigneeCount);

            $task = new Task([
                'title' => $title,
                'description' => $desc,
                'status' => $status,
                'priority' => $priority,
                'category' => $category,
                'due_date' => $dueOffset === null ? null : ($dueOffset >= 0 ? now()->addDays($dueOffset) : now()->subDays(abs($dueOffset))),
                'supervisor_id' => $supervisor->id,
                'created_by' => $admin->id,
                'position' => $position++,
            ]);
            // Bypass the saving status guard — there is no "original" yet,
            // but the gate sometimes flags fresh rows when auth() is null.
            $task->skipStatusGuard = true;
            $createdAt = now()->subDays(rand(3, 55));
            $task->created_at = $createdAt;
            $task->updated_at = $createdAt->copy()->addHours(rand(1, 200));
            $task->save();

            $task->assignees()->syncWithPivotValues($assignees->pluck('id')->all(), ['assigned_at' => now()]);

            // TESTING / DONE require a proof per Task::booted gate.
            if (in_array($status, [Task::STATUS_TESTING, Task::STATUS_DONE], true)) {
                TaskProof::create([
                    'task_id' => $task->id,
                    'user_id' => $assignees->first()?->id ?? $supervisor->id,
                    'kind' => TaskProof::KIND_NOTE,
                    'title' => 'Bench notes',
                    'body' => 'Tested locally on the bench. Output matches expected envelope.',
                ]);
            }

            $createdTasks[] = $task;

            // DatabaseSeeder uses WithoutModelEvents so the lifecycle
            // listeners on LogsActivity never fire during seeding. Log a
            // "created" entry by hand so the audit page has data to show.
            $task->logActivity('created');
        }

        // Comments on a subset of tasks.
        $commentTexts = [
            'Pulled this into the current sprint.',
            'Blocked on the new sensor delivery (ETA Friday).',
            'Bench result attached — looks within tolerance.',
            'Can you re-run with the updated firmware build?',
            'Rebased on main, conflicts resolved.',
            'Készen vagyok vele, áttolnám TESTING-be.',
            'Merged — closing once the static fire passes.',
            'Need a second pair of eyes on the wiring diagram.',
        ];
        foreach (array_slice($createdTasks, 0, 8) as $i => $task) {
            $count = 1 + ($i % 3);
            for ($c = 0; $c < $count; $c++) {
                $author = $assigneePool[($i + $c) % $assigneePool->count()];
                $createdAt = $task->created_at->copy()->addHours(rand(2, 240));
                TaskComment::create([
                    'task_id' => $task->id,
                    'user_id' => $author->id,
                    'body' => $commentTexts[($i + $c) % count($commentTexts)],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        // Parent / children: pick a couple of existing tasks and graft
        // children. Children added after creation never trip the parent
        // auto-promote rule because the children inherit the parent's
        // status at insert time.
        $parents = collect($createdTasks)->take(2);
        foreach ($parents as $pIdx => $parent) {
            for ($c = 0; $c < 3; $c++) {
                $supervisor = $supervisors[($pIdx + $c) % $supervisors->count()];
                $assignee = $assigneePool[($pIdx + $c) % $assigneePool->count()];
                $childStatus = $parent->status === Task::STATUS_DONE ? Task::STATUS_DONE : Task::STATUS_IN_PROGRESS;
                $child = new Task([
                    'title' => $parent->title . ' — subtask ' . ($c + 1),
                    'description' => 'Sub-task split out from "' . $parent->title . '".',
                    'status' => $childStatus,
                    'priority' => $parent->priority ?? Task::PRIORITY_NORMAL,
                    'category' => $parent->category ?? Task::CATEGORY_OTHER,
                    'parent_task_id' => $parent->id,
                    'supervisor_id' => $supervisor->id,
                    'created_by' => $admin->id,
                    'position' => $position++,
                ]);
                $child->skipStatusGuard = true;
                $child->save();
                $child->assignees()->syncWithPivotValues([$assignee->id], ['assigned_at' => now()]);
                if ($childStatus === Task::STATUS_DONE) {
                    TaskProof::create([
                        'task_id' => $child->id,
                        'user_id' => $assignee->id,
                        'kind' => TaskProof::KIND_NOTE,
                        'title' => 'Subtask done',
                        'body' => 'Completed as part of the parent task.',
                    ]);
                }
                $child->logActivity('created');
            }
        }
    }

    private function seedContacts(): void
    {
        $groups = [
            ['name' => "Sponsors' reps", 'description' => 'Primary contacts at sponsoring companies.'],
            ['name' => 'Suppliers',      'description' => 'Component + material suppliers.'],
            ['name' => 'Partner orgs',   'description' => 'Universities, labs, allied teams.'],
            ['name' => 'Press',          'description' => 'Journalists + media contacts.'],
            ['name' => 'University admin', 'description' => 'Faculty + administrative liaisons at Óbuda.'],
        ];

        $createdGroups = [];
        foreach ($groups as $i => $g) {
            $group = ContactGroup::create($g + ['position' => $i]);
            $group->logActivity('created');
            $createdGroups[] = $group;
        }

        $contacts = [
            // Sponsors
            ['Kovács Réka',      '+36 30 111 2233', 'reka.kovacs@orbital-systems.hu', 'Account manager — Orbital Systems Hungary.', 0],
            ['Szilágyi Tamás',   '+36 70 444 5566', 'tamas.szilagyi@aero-supply.eu', 'CTO — Aero Supply EU.',                       0],
            ['Molnár Petra',     '+36 20 987 6543', 'petra.molnar@composite-tech.hu', 'Marketing lead — Composite Tech.',           0],
            // Suppliers
            ['Németh Gábor',     '+36 1 222 3344',  'gabor.nemeth@elektroplus.hu',    'Sales — Elektro Plus Kft.',                  1],
            ['Farkas Eszter',    '+36 30 555 1212', 'eszter.farkas@fibers.hu',         'CF tube + epoxy supplier.',                 1],
            ['Tóth Bálint',      '+36 70 333 9090', 'balint.toth@cnc-works.hu',        'Machining + welding shop.',                 1],
            // Partner orgs
            ['Dr. Szabó László', '+36 1 666 7700',  'szabo.laszlo@bme.hu',             'BME aerospace lab — wind tunnel access.',   2],
            ['Dr. Varga Anikó',  '+36 70 888 1313', 'aniko.varga@elte.hu',             'ELTE atmospheric physics — telemetry data.', 2],
            ['Stefan Müller',    '+49 30 1234 5678', 'stefan.mueller@deutsche-rocket.de', 'Deutsche Rocketry e.V. — partner team.',  2],
            // Press
            ['Horváth Júlia',    '+36 30 222 4488', 'horvath.julia@hvg.hu',            'HVG science desk.',                          3],
            ['Balogh Zsolt',     '+36 20 555 0011', 'zsolt.balogh@telex.hu',           'Telex tech reporter.',                      3],
            // University admin
            ['Dr. Papp István',  '+36 1 666 5544',  'papp.istvan@uni-obuda.hu',        'Faculty liaison — Bánki Donát kar.',         4],
            ['Kiss Erzsébet',    '+36 1 666 5500',  'kiss.erzsebet@uni-obuda.hu',      'Student affairs office — funding contact.', 4],
            ['Dr. Lakatos Péter', '+36 70 444 8899', 'lakatos.peter@uni-obuda.hu',     'Dean of student research.',                  4],
        ];

        foreach ($contacts as $i => [$name, $phone, $email, $notes, $groupIdx]) {
            $contact = Contact::create([
                'contact_group_id' => $createdGroups[$groupIdx]->id,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'notes' => $notes,
                'position' => $i,
            ]);
            $contact->logActivity('created');
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $reporters
     * @param  \Illuminate\Support\Collection<int, User>  $admins
     */
    private function seedBugReports($reporters, $admins): void
    {
        $bugs = [
            [
                'title' => 'Kanban drag occasionally drops the wrong card on Safari',
                'description' => 'On Safari 17, dragging a card from IN_PROGRESS to TESTING sometimes moves the card above it instead. Reproducible with the avionics task.',
                'severity' => BugReport::SEVERITY_HIGH,
                'status' => BugReport::STATUS_IN_PROGRESS,
                'page_url' => '/admin/tasks/kanban',
                'created_at' => now()->subDays(3),
            ],
            [
                'title' => 'Calendar event modal close button cut off on mobile',
                'description' => 'Below ~360 px width the close X is partly outside the viewport.',
                'severity' => BugReport::SEVERITY_LOW,
                'status' => BugReport::STATUS_TRIAGING,
                'page_url' => '/admin/calendar',
                'created_at' => now()->subDays(8),
            ],
            [
                'title' => 'Export GLB shows "running" forever on a deleted document',
                'description' => 'If the Onshape source doc was deleted, the re-export action gets stuck in running with no error surfaced.',
                'severity' => BugReport::SEVERITY_MEDIUM,
                'status' => BugReport::STATUS_OPEN,
                'page_url' => '/admin/onshape-models/2/edit',
                'created_at' => now()->subDays(1),
            ],
            [
                'title' => 'Locale switcher resets to EN after password change',
                'description' => 'The HU pick is lost after the forced password change on first login.',
                'severity' => BugReport::SEVERITY_MEDIUM,
                'status' => BugReport::STATUS_RESOLVED,
                'page_url' => '/admin/profile',
                'created_at' => now()->subDays(20),
                'resolved_at' => now()->subDays(15),
            ],
            [
                'title' => 'Drawing studio crashes on iPad with large canvases',
                'description' => 'On iPad Pro 12.9, picking the 4096×4096 preset reliably crashes the page.',
                'severity' => BugReport::SEVERITY_CRITICAL,
                'status' => BugReport::STATUS_OPEN,
                'page_url' => '/admin/drawings/draw',
                'created_at' => now()->subHours(6),
            ],
            [
                'title' => 'Activity log search ignores diacritics',
                'description' => "Searching for 'Bálint' doesn't match 'Balint'. Acceptable but the filter help suggests it would.",
                'severity' => BugReport::SEVERITY_LOW,
                'status' => BugReport::STATUS_WONT_FIX,
                'page_url' => '/admin/activity-logs',
                'created_at' => now()->subDays(40),
                'resolved_at' => now()->subDays(38),
                'admin_notes' => 'SQLite LIKE has no built-in collation that handles this; out of scope.',
            ],
        ];

        foreach ($bugs as $i => $row) {
            $reporter = $reporters[$i % $reporters->count()];
            $assignee = $admins[$i % $admins->count()];
            $createdAt = $row['created_at'];
            $resolvedAt = $row['resolved_at'] ?? null;
            unset($row['created_at'], $row['resolved_at']);

            $bug = new BugReport($row + [
                'reporter_id' => $reporter->id,
                'assignee_id' => in_array($row['status'], BugReport::openStatuses(), true) ? $assignee->id : null,
            ]);
            $bug->created_at = $createdAt;
            $bug->updated_at = $resolvedAt ?? $createdAt;
            if ($resolvedAt) {
                $bug->resolved_at = $resolvedAt;
            }
            $bug->save();
            $bug->logActivity('created');
        }
    }

    private function seedOnshapeModels(User $admin): void
    {
        $rows = [
            [
                'title' => 'Helios — fuselage assembly',
                'description' => 'Top-level assembly for the Helios mid-power airframe.',
                'document_id' => '08d6a3b6e4a5f9c0aa771122',
                'workspace_id' => '5f1a7c4d8e9b3201cc445566',
                'element_id' => '3e7b8a9c1d2f0410ee998877',
                'share_url' => 'https://cad.onshape.com/documents/08d6a3b6e4a5f9c0aa771122/w/5f1a7c4d8e9b3201cc445566/e/3e7b8a9c1d2f0410ee998877',
            ],
            [
                'title' => 'Atlas-1 — recovery bay',
                'description' => 'Dual-deployment recovery bay sub-assembly.',
                'document_id' => '11a2b3c4d5e6f70811223344',
                'workspace_id' => '99887766554433221100aabb',
                'element_id' => null,
                'share_url' => 'https://cad.onshape.com/documents/11a2b3c4d5e6f70811223344/w/99887766554433221100aabb',
            ],
        ];

        foreach ($rows as $row) {
            $model = OnshapeModel::create(array_merge($row, [
                'user_id' => $admin->id,
                'glb_status' => OnshapeModel::GLB_IDLE,
            ]));
            $model->logActivity('created');
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TeamMember>  $teamMembers
     */
    private function seedInventory($teamMembers): void
    {
        $office = Venue::where('key', Venue::KEY_OFFICE)->first();
        $private = Venue::where('key', Venue::KEY_PRIVATE)->first();
        if (! $office || ! $private) return;

        $catalog = [
            'Solder iron', 'Multimeter', 'Oscilloscope',
            'Carbon fiber tube — 50mm', 'Epoxy resin 1L', 'Servo motor SG90',
            'Raspberry Pi 4', 'GPS module', 'Heat shrink kit', 'Drill bits set',
        ];

        $items = [];
        foreach ($catalog as $name) {
            $items[] = Item::create(['name' => $name]);
        }

        // 2 office stocks per item — most kit lives at the lab.
        foreach ($items as $i => $item) {
            ItemStock::create([
                'item_id' => $item->id,
                'venue_id' => $office->id,
                'owner_team_member_id' => null,
                'quantity' => match (true) {
                    str_contains($item->name, 'kit')          => 5,
                    str_contains($item->name, 'tube')         => 12,
                    str_contains($item->name, 'resin')        => 4,
                    str_contains($item->name, 'Servo')        => 8,
                    str_contains($item->name, 'GPS')          => 3,
                    str_contains($item->name, 'Raspberry')    => 2,
                    str_contains($item->name, 'Oscilloscope') => 1,
                    default                                   => 2,
                },
            ]);
        }

        // A handful of private-venue stocks (a couple of tools each, with
        // an owner). Skip the private-venue items that wouldn't make
        // sense (bulk consumables stay at the lab).
        $loanable = collect($items)->reject(fn ($i) => str_contains($i->name, 'tube') || str_contains($i->name, 'resin'))->values();
        $owners = $teamMembers->take(6)->values();
        foreach ($loanable as $i => $item) {
            $owner = $owners[$i % $owners->count()];
            ItemStock::create([
                'item_id' => $item->id,
                'venue_id' => $private->id,
                'owner_team_member_id' => $owner->id,
                'quantity' => 1,
            ]);
        }
    }
}

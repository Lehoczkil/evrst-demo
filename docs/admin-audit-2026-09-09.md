# Admin audit — 2026-09-09

A Filament panel (`backend/app/Filament/**`, 124 fájl / ~8 400 sor) plusz a hozzá
tartozó middleware, provider és modellréteg átolvasása. 22 találat, súlyosság
szerint rendezve, mindegyikhez `fájl:sor` és — ahol mérhető volt — bizonyíték.

## Módszer

- Statikus átolvasás: jogosultsági kapuk (`can*`), Livewire-akciók, tranzakciók,
  eager loading, cache-kulcsok, lokalizáció.
- Ahol a kód alapján nem lehetett biztosat mondani, eldobható próbateszt futott
  Member-szerepű felhasználóval (a fájl törölve, nem került a repóba). A
  `PROBE:` jelölésű bizonyítékok ebből származnak.
- A Filament vendor-kódja két állításnál lett ellenőrizve: a policy-nélküli
  authorizáció **engedélyező** (`vendor/filament/filament/src/helpers.php:80-94`,
  `Response::allow()`), és a `filament/tables` **nem** végez automatikus eager
  loadingot (nincs rá kód a csomagban).

---

## P0 — nyitott kapuk

### 1. `ItemResource`-on nincs egyetlen jogosultsági kapu sem

`app/Filament/Resources/Items/ItemResource.php` — nulla `can*()` metódus.
Policy sincs (`app/Policies` nem létezik), és a Filament ilyenkor **engedélyez**.
Következmény: minden bejelentkezett felhasználó, Member is, teljes CRUD-ot kap a
tételkatalógusra. Ugyanez az `ItemManagement` oldalon (`app/Filament/Pages/ItemManagement.php`),
ahol nincs `canAccess()` — az az `item_stocks` tábla, tehát a leltár.

```
PROBE member GET /admin/items          => 200
PROBE member GET /admin/items/create   => 200
PROBE member GET /admin/item-management => 200
```

Súlyosbító körülmény: a `Perm` katalógusban **nincs** `items.*` / `venues.*` kulcs
(37 kulcs, egyik sem erről szól), tehát a javításhoz új `Perm::*` kulcsok kellenek
— és azokhoz a CLAUDE.md szabálya szerint backfill migráció, mert a `RoleSeeder`
egyszer fut.

Döntés: Minden felhasználó kezelheti az itemeket, de legyen egy log, ahol követni lehet, hogy mikor és ki milyen módosítást végzett a táblán.

### 2. A Calendar oldal írható minden tagnak

`app/Filament/Pages/Calendar.php` — a docblock szerint „Admin-only month calendar",
a valóságban nincs `canAccess()`, és a `saveEvent()` (`:332`), `deleteEvent()`
(`:392`), `openEditModal()` (`:284`) publikus Livewire-metódusok mindenféle
jogosultsági ellenőrzés nélkül. `openEditModal(int $id)` bármelyik esemény
azonosítóját elfogadja, `deleteEvent()` pedig azt törli, ami az `editingId`-ben van.

```
PROBE member GET /admin/calendar        => 200
PROBE member created calendar event     => YES
```

Mellékhatás: az esemény létrehozása Discord-webhookot is posztol (`:383`), tehát
bárki üzenetet küldhet a csapatcsatornába a panelen keresztül.

Döntés: Csak admin kapjon jogosultságot ehhez, de mindenki láthassa.

### 3. `User::updateOrCreate` a login-provisioning két helyén

- `EditTeamMember.php:125` (**Create login** header action)
- `AcceptMemberApplication.php:152` (accept flow)

Ha az adott címen **már van** user, ez felülírja a jelszavát, `password_changed_at`-ot
null-ra állítja és a `role_id`-t Member-re. Vagyis egy Admin egyetlen kattintással
lefokozható és kizárható.

Az accept flow ellen véd a form `->unique(table: User::class, column: 'email')`
szabálya. A **Create login** action viszont csak azt nézi, hogy
`! $record->user_id` — és pontosan az az állapot áll elő az `org-email:realign`
után vagy kézi javításnál, hogy a TeamMember `user_id`-je null, miközben a
címen létezik user. Ott nincs semmilyen guard.

DÖntés: Ezt azonnal javítsd

### 4. A dashboard kikerüli a saját jogosultsági kapuit

`app/Filament/Pages/Dashboard.php` + `resources/views/filament/pages/dashboard.blade.php`
— a Blade feltétel nélkül rendereli a jelentkezők listáját és az activity feedet,
holott a `MemberApplicationResource::canViewAny()` az `applications.view` permhez
(csak Admin) van kötve, az `ActivityLogResource` pedig admin-only.

```
PROBE dashboard leaks applicant email      => no   (a query kiszedi, a Blade nem írja ki)
PROBE dashboard leaks applicant NAME       => YES
PROBE dashboard shows applications link    => YES  (403-ba fut kattintásra)
PROBE dashboard shows activity tile        => YES
```

Tehát nem email szivárog, hanem **jelentkezők neve + státusza** és a **teljes
audit-feed** (ki mit módosított a panelen), plusz vezet egy link egy 403-as oldalra.

Döntés: Ezt is sűrgősen javítsd, csak admin lássa. Ezen kívül eleve nézd meg a dashbordot, mert nem jelenik meg rendesen, a csempés designnal, csak szöveg fekete háttéren.
---

## P1 — helyességi hibák

### 5. A „nem küldhető" guard csak a bulk útvonalon van meg

`UsersTable::isDeliverable()` (`:220`) pontosan azért készült, hogy ne rotáljunk
jelszót olyan tagnak, akinek nincs hova küldeni (`@evrst.hu` = csak login, nincs
postafiók). Ez a guard **három** másik helyről kimaradt:

| Hely | Rotál | Guard |
| --- | --- | --- |
| `UsersTable` bulk (`:189`) | igen | **van** |
| `UsersTable` row action (`:98`) | igen | nincs |
| `EditTeamMember::create_login` (`:125`) | igen | nincs |
| `CreateUser` | igen | nincs |

Nyári György és Som Nemere pontosan ez az eset (nincs `email_private`). A row
action rájuk lefutva kizárja őket: az új jelszó egy nem létező postafiókba megy,
a régi már nem érvényes.

Döntés: Javítsd

### 6. A jelszórotálás nem visszaállítható hiba esetén

`UsersTable:98-116` és `:195-206` — előbb megy a `$record->update([...])`, aztán a
küldés. Ha a `sendNow` dob (SMTP-hiba, hibás cím), a jelszó már ki van cserélve, és
az új értéket senki nem ismeri. A bulk útvonal ezt `$failed[]`-ként jelenti, de nem
állítja vissza. Javítás: a régi `password` + `password_changed_at` elmentése, és
rollback a catch-ágban.

Döntés: Javítsd

### 7. A Member szerep sosem éri el a task-flow-t, amit neki írtunk

- `Task::canTransitionTo()` (`app/Models/Task.php:208`): `if (! $user->can(TASKS_EDIT)) return false;`
- `TaskResource::canEdit()`: ugyanaz a perm → a task edit oldal 403
- `Perm::memberPermissions()`: `bugs.report`, `bugs.view`, `contacts.view` — semmi task

```
PROBE assigned member GET /admin/tasks/{id}/edit => 403
```

Ugyanakkor a `ProofsRelationManager::canPostProof()` (`:160`) docblockja szó szerint
ezt írja: *„Members without TASKS_EDIT can still upload evidence on a task they're
assigned to."* Ez a kód **halott** — a relation manager csak az edit oldalon
renderel, ahova a Member nem jut be. Ugyanez a `CommentsRelationManager`-re.

Vagyis a dokumentált állapotgép (assignee → TESTING proof-fal, supervisor → DONE)
nem elérhető annak a szerepnek, akire kitalálták. Ez döntést kíván: per-rekord
`canEdit` az assignee-knek, vagy külön `tasks.progress` perm.

Döntés: Javítsd

### 8. Az accept flow nincs tranzakcióban

`AcceptMemberApplication::save()` (`:148-190`): user létrejön → `TeamMember::create()`
elhasalhat a `team_members.email` unique indexén (a soft-deleted sorokra is áll) →
500-as hibaoldal, marad egy orphan user `password_changed_at = null`-lal, a
jelentkezés meg PENDING-en. Egy `DB::transaction()` megoldja.

Döntés: Javítsd

### 9. `CreateUser` rossz címet jelent és nincs hibakezelése

`CreateUser.php:33-37`:
- `$this->record->notify(...)` — a másik négy hely `sendNow`-ot használ, mert
  „az admin lássa a valódi eredményt"; itt nincs try/catch, tehát mailer-hiba
  500-at ad a user létrehozása **után**.
- A visszajelzés `$this->record->email`-t (a login címet) írja ki, miközben a levél
  `deliveryEmail()`-re (`email_private`) megy → az admin hamis címet lát.
- Nincs `mail.default === 'log'` figyelmeztetés, pedig a másik négy helyen van.
- `$data['password_changed_at'] = null` (`:26`) akkor is, ha az admin kézzel adott
  jelszót — az így felvett user is a kényszerített csere kapujába fut.

  Döntés: Javítsd

### 10. A „pending applications" tile nem szűr státuszra

`Dashboard::getPendingApplications()` (`:88`) — nincs `where('status', PENDING)`,
csak `orderByDesc(created_at)->limit(5)`. A nagy szám (`$stats['pending']`) viszont
csak a PENDING-eket számolja. Így a „3 várakozó" alatt elfogadott/elutasított
jelentkezők is felsorolódnak. Ráadásul a `getLatestApplications()` (`:172`)
karakterre ugyanez a query — kettő közül az egyik felesleges.

Döntés: Javítsd

### 11. Sosem invalidált cache-kulcsok

`AppServiceProvider::wireWidgetCacheInvalidation()` a doc szerint azért létezik,
hogy „a 60 s TTL soha ne mutasson elavult számot". Három kulcs kimaradt:

- `widgets:latest-applications` — csak olvasva, soha nem törölve
- `widgets:dashboard-activity` — ugyanaz
- `bugs:open-count` (a nav-badge) — ugyanaz
- `CalendarEvent` írások semmit nem invalidálnak, pedig a `widgets:upcoming-schedule`
  tartalmaz calendar eventeket

Továbbá a `Task::saved` hook (`:100`) az *aktuális* assignee-k cache-ét törli, tehát
egy eltávolított assignee-nél a régi kulcs bennmarad.

Döntés: Javítsd

### 12. A pivot időbélyegek nem íródnak

`AcceptMemberApplication:177` és `EditTeamMember:59`:
`groups()->sync(array_fill_keys($ids, ['is_primary' => false]))` — nincs
`started_at`. A `team_member_team_member_group` pivotnak külön `id` PK-ja van
pontosan azért, hogy ugyanaz a pozíció több időszakon átfogható legyen
(`started_at` / `ended_at`), de a panelről felvett hozzárendelések üres kezdődátummal
jönnek létre, így az egész idővonal-képesség használatlan.

Döntés: Javítsd
---

## P2 — teljesítmény

### 13. N+1 nyolc táblán

A Filament nem eager-loadol automatikusan (ellenőrizve a vendorban). Relációs
kolumna van, `modifyQueryUsing(with(...))` nincs:

`OnshapeModelsTable` (`user.name`), `TasksTable` (`assignees.name`, `parent.title`,
`supervisor.name`), `DrawingsTable` (`user.name`), `ResourcesTable` (`collection.name`),
`BugReportsTable` (`assignee.name`, `reporter.name`), `UsersTable` (`role.name`),
`TeamMemberGroupsTable` (`parent.name`). Egyedül a `ContactResource::getEloquentQuery()`
teszi jól (`->with('group')`).

A Users tábla a legrosszabb: a `notification_email` kolumna három closure-ből
(`state`, `color`, `tooltip`) hívja a `deliveryEmail()`-t, ami `loadMissing('teamMember')`-t
csinál soronként.

Döntés: Javítsd

### 14. A kanban drag soronként dolgozik

`KanbanBoard::reorder()` — `Task::find($taskId)` (`:184`) és külön `save()` minden
kártyára, egy tranzakción belül. Minden `save()` meghívja az `AppServiceProvider`
`Task::saved` hookját, ami még egy `assignees()->pluck()`-ot fut. Egy 40 kártyás
tábla átrendezése így ~120 query egyetlen írásra képes SQLite ellen. `findMany()`
+ `keyBy()` és a hook egyszeri futtatása a megoldás.

Döntés: Javítsd

### 15. `DatabaseInspector` minden rendernél megszámol mindent

`getTablesIndex()` — `COUNT(*)` minden táblára, cache nélkül, minden oldalbetöltésnél.

Döntés: Javítsd

---

## P3 — konzisztencia és refaktor

### 16. Az ideiglenes jelszó kiadása öt helyen, ötféleképpen

`UsersTable` row action, `UsersTable` bulk, `EditTeamMember::create_login`,
`CreateUser`, `AcceptMemberApplication`. Eltérések: deliverability-guard csak
egyben, rollback egyikben sem, `sendNow` háromban / `notify` egyben, a jelentett
célcím kettőben hibás, a `log` mailer figyelmeztetés háromban van meg. Ez a
4., 5., 6. és 9. találat közös gyökere.

Javaslat: egy `App\Actions\IssueTempPassword` osztály, ami (a) `deliveryEmail()`-ből
oldja fel a célcímet, (b) elutasítja a nem küldhetőt, (c) rotál, küld, és bukásnál
visszaáll, (d) egységes riportot ad vissza (sent / skipped / failed + mailer-állapot).
Mind az öt hívó erre redukálódik.

Döntés: Javítsd

### 17. 39 beégetett angol string

14 fájlban, `->title('…')` / `->label('…')` / `->modalHeading('…')` formában
(`AcceptMemberApplication` 8, `ResourcesTable` 5, `Draw` 4, `TeamMemberGroupForm` 4,
`KanbanBoard` 3, …). Az EN/HU váltó kiadott feature, tehát ezek HU alatt is angolul
jelennek meg.

Döntés: Javítsd

### 18. `KanbanBoard::reorder()` felülírja a saját paraméterét

`:228` — a notification-ciklusban `$payload = DiscordPayloads::...` ugyanaz a
változónév, mint a metódus `array $payload` paramétere. Ma nem okoz hibát (a
tranzakció már lezárult), de pontosan az a fajta árnyékolás, ami később fog.

Döntés: Javítsd

### 19. `Cms\` névtér a team member / group erőforrásokon

Dokumentált leftover (`app/Filament/Resources/Cms/TeamMembers/`), a modellek már
nem CMS-esek. A `/admin/cms/team-members` URL-ek miatt maradt.

Döntés: Javítsd

### 20. A `PermissionsTest` route-listája hiányos

`adminOnlyRoutesProvider` lefedi a roles / activity-logs / database-inspector /
member-applications / users útvonalat, de nem a `/admin/items`,
`/admin/item-management`, `/admin/calendar` hármat. Ezért csúszhatott ki az 1. és
2. találat. A javításnak részévé kell tenni a listát, különben visszatér.

Döntés: Javítsd

### 21. `RequirePasswordChange` minden `livewire/*` utat átenged

`app/Http/Middleware/RequirePasswordChange.php` — szükséges, hogy a profil-űrlap
működjön, de mellékhatásként a még nem aktivált user a profil oldal topbarjából
használhatja a globális keresés Livewire-komponensét, azaz rekordokat listázhat,
mielőtt jelszót állított volna. Alacsony kockázat (belső felhasználó), de a kapu
így nem szigetel.

Döntés: Javítsd

### 22. Az Onshape GLB export szinkron egy webkérésben

`EditOnshapeModel:47` — `dispatchSync()`, benne külső API-hívás + bináris letöltés.
Az indoklás („nincs queue worker") a compose stackben már nem áll: fut egy `queue`
konténer. Timeout-kockázat egy hosszú Onshape-transzlációnál.

Döntés: Javítsd

---

## Javítási terv

A fázisok egymásra épülnek; mindegyik önmagában commitálható. Az idők
egy-fejlesztős becslések.

### 0. fázis — nyitott kapuk bezárása (~45 min, ma)

1. `ItemResource` + `ItemManagement`: `canViewAny/canCreate/canEdit/canDelete` +
   `canAccess`. Új `Perm::ITEMS_VIEW|CREATE|EDIT|DELETE` kulcsok, **plusz backfill
   migráció** (`permissions` insert + `permission_role` csatolás Admin/Manager
   szerepre) a `2026_05_03_000007_attach_models_permissions.php` mintájára.
2. `Calendar`: `canAccess()`, és minden publikus Livewire-metódus élén
   `abort_unless()` — az `openEditModal` / `deleteEvent` esetében a rekordra is.
3. `Dashboard`: a jelentkező- és activity-tile Blade-ben perm mögé
   (`applications.view`, illetve `isAdmin()`), és a query is csak akkor fusson.
4. `PermissionsTest::adminOnlyRoutesProvider` bővítése a három útvonallal (20. találat).

Döntés kell hozzá: az Items/leltár Member-nek **olvasható** legyen-e (mint az
Events/Team), vagy egyáltalán ne látszódjon; és a Calendar admin-only marad-e,
vagy Member-olvasható + admin-írható.

### 1. fázis — kizárás-család (~2 h)

5. `App\Actions\IssueTempPassword` kiemelése (16. találat), rollback-kel (6.) és
   deliverability-guarddal (5.).
6. Mind az öt hívó átvezetése rá; `CreateUser` célcím-jelentése javítva (9.).
7. `EditTeamMember::create_login`: `updateOrCreate` → létező user esetén
   megtagadás + magyarázó notification, a `syncLoginEmail()` konfliktuskezelésének
   mintájára (3.). Az accept flow-ban `create()`-re szűkítés, a form unique
   szabálya mellé.
8. Tesztek: rotálás nem küldhető címre = skip; mailer-hiba = a régi jelszó marad;
   létező címre `create_login` = megtagadva.

### 2. fázis — a Member task-flow (~2 h)

9. Döntés: (a) `TaskResource::canEdit($record)` engedje az assignee-t és a
   supervisort per rekord, a form érzékeny mezői külön gate-elve; vagy
   (b) új `tasks.progress` perm a Member szerepnek, backfill migrációval.
10. `Task::canTransitionTo()` 208. sora ehhez igazítva.
11. `KanbanBoard::canEditTasks()` per-kártya ellenőrzésre cserélve (ma egy globális
    perm dönt az egész tábláról).
12. Teszt: assignee eljut az edit oldalra, feltölt proofot, TESTING-re tolja,
    DONE-t nem tud; supervisor tud.

### 3. fázis — adatintegritás (~1 h)

13. `AcceptMemberApplication::save()` `DB::transaction()`-ba (8.).
14. `started_at => now()` mindkét pivot-sync helyre (12.).
15. `getPendingApplications()` státuszszűrés + a duplikált
    `getLatestApplications()` megszüntetése (10.).
16. A három hiányzó cache-invalidáció + `CalendarEvent` hookok (11.).

### 4. fázis — teljesítmény (~1 h)

17. `modifyQueryUsing(fn ($q) => $q->with([...]))` a nyolc táblára; a Users
    táblánál `teamMember` + `role` (13.).
18. `KanbanBoard::reorder()` `findMany()`-re, a `Task::saved` cache-hook egyszeri
    futtatásával (14.).
19. `DatabaseInspector` sorszámai `Cache::remember(60)`-nal (15.).

### 5. fázis — polish (~1,5 h)

20. A 39 string kiszervezése `lang/{en,hu}/admin.php`-be (17.).
21. `$payload` átnevezése a kanbanban (18.).
22. Onshape export queue-ra állítása `dispatchSync` helyett, státusz-pollinggal,
    vagy explicit `set_time_limit()` + felhasználói figyelmeztetés (22.).
23. `RequirePasswordChange`: a livewire allow-list szűkítése a profil-komponensre
    (21.) — csak ha a Livewire snapshot-név stabilan felismerhető, különben marad
    és dokumentált korlát lesz.
24. `Cms\` névtér kivezetése route-alias mellett (19.) — opcionális, kozmetikai.

## Amit nem találtam

Ezek átnézve, rendben:

- `BugReportResource::getEloquentQuery()` — a nem-triagerek csak a saját
  jelentéseiket látják, és mivel a resolve is ezen a queryn megy, URL-ből sem
  nyitható meg más jelentése.
- `DatabaseInspector` — `canAccess()` + `abort_unless` a `mount()`-ban, és a
  `selectTable()` whitelist ellen validál.
- `Draw::save()` — data-URL regex, `base64_decode(strict)`, `abort_unless` a
  create/edit jogra. (Méretkorlát nincs, de a `.user.ini` post_max_size behatárolja.)
- `SetLocale` — a `?lang` bemenet két karakterre vágva, whitelist ellen ellenőrizve.
- `Perm::catalog()` — 37 kulcs, mindegyikhez van konstans és mindegyik benne van
  az adatbázisban is (admin 37 / manager 29 / member 3), tehát nincs elfelejtett
  backfill migráció.
- Blade-ek — két `{!! !!}` van, mindkettő belsőleg előállított értéket ír ki;
  a `HtmlString` a `UsersTable`-ben soronként `e()`-vel escape-el.
- Nincs `dd()` / `dump()` / TODO maradék az admin fában, és mind a 124 fájl
  szintaktikailag rendben.

---

## Elvégezve — 2026-09-09

Mind a 22 találat javítva a fenti döntések szerint. Ami eltér az eredeti
javaslattól, azt külön jelzem.

### P0

1. **Items / leltár** — a döntés szerint **nyitva maradt** mindenkinek, cserébe
   új **`/admin/item-log`** oldal (`App\Filament\Pages\ItemLog`): az `Item` +
   `ItemStock` `activity_logs` sorai, olvasható diffel, ugyanannak a körnek, aki
   szerkeszthet. (A teljes audit-feed a Membership → Activity log alatt marad,
   továbbra is admin-only.) Új `Perm::ITEMS_*` kulcs és backfill migráció így
   **nem** kellett.
2. **Calendar** — mindenki olvashatja, csak admin írhatja. `canManageEvents()`
   + `abort_unless()` az `openCreateModal` / `confirmPastDate` / `openEditModal` /
   `saveEvent` / `deleteEvent` elején, a Blade pedig a kattintható felületeket is
   elrejti. (A Livewire-végpont a Blade-től függetlenül hívható, ezért kell a
   szerveroldali kapu is.)
3. **`User::updateOrCreate`** — megszűnt mindkét helyen. Az új
   `App\Support\MemberLogin::provision()` **összekapcsolja** a meglévő fiókot
   (jelszót nem nyúl hozzá), az accept flow pedig megtagadja a mentést és
   megmondja, miért.
4. **Dashboard** — a jelentkező- és activity-csempe `canSeeApplications()` /
   `canSeeActivity()` mögé került, a query is csak akkor fut. A gyorsgombok közül
   kiesik, amit az adott felhasználó úgysem tudna megnyitni.
   **A „csak szöveg fekete háttéren" oka nem a Blade volt:** a
   `deploy/Caddyfile` `@backend` matchere nem tartalmazta a
   `/css/admin-theme.css`-t (és a `/vendor/*`-ot), így élesben a SPA
   `try_files`-a `index.html`-t adott vissza a stíluslap helyett — a böngésző
   pedig eldobta. Mindkét útvonal bekerült a matcherbe; **újradeploy után** jön
   vissza a csempés design (és a kanban SortableJS-e).

### P1

5–6., 9., 16. **Egy útvonal maradt**: `App\Actions\IssueTempPassword`
   (`rotate()` / `deliver()`), `TempPasswordResult` + `App\Filament\Support\TempPasswordReport`
   a jelentéshez. Kézbesíthetetlen címnél **rotálás előtt** elutasít; küldési
   hibánál visszaállítja a régi hasht; a jelentett cím mindenhol a
   `deliveryEmail()`. Mind az öt hívó erre redukálódott. A `CreateUser` a kézzel
   megadott jelszónál már `password_changed_at = now()`-t ír, tehát nem futtatja
   feleslegesen a kényszerített cserébe.
7. **Member task-flow** — új `Perm::TASKS_PROGRESS` kulcs + backfill migráció
   (`2026_09_09_000001_attach_tasks_progress_permission`). `Task::canBeProgressedBy()`
   dönt rekordonként, erre épül a `TaskResource::canEdit($record)`, a
   `canTransitionTo()` és a kanban. A `TaskForm` definíciós mezői (cím, leírás,
   felelős, határidő, prioritás, kategória, szülő, sorrend) zárolva vannak annak,
   akinek csak `tasks.progress`-e van — a Filament nem dehidratálja a disabled
   mezőt, így a tárolt érték nem írható felül.
8. **Accept flow** — egy `DB::transaction()`.
10. **Pending tile** — a duplikált, szűretlen query törölve; a csempe a
    `getStats()['pending']` számot mutatja, a lista a „legutóbbi jelentkezések"
    csempén marad, státuszjelöléssel.
11. **Cache** — `widgets:recent-applications`, `widgets:dashboard-activity`
    (`ActivityLog::created`), `bugs:open-count` (`BugReport::saved/deleted`) és a
    `CalendarEvent` → `widgets:upcoming-schedule` hookok bekötve. A my-tasks
    kulcs **verziózott** (`Task::myTasksCacheVersion()`): egy írás mindenki
    listáját érvényteleníti, így a taskról levett felhasználóé is — és elmarad a
    mentésenkénti `assignees()->pluck()`.
12. **Pivot időbélyeg** — `TeamMember::syncGroupAssignments()` mindhárom hívónál.
    `started_at` csak az **újonnan** felvett sorokra kerül, különben minden
    mentés újraírná az előzményt.

### P2

13. `modifyQueryUsing(with(...))` a nyolc táblán (a Users-nél `role` + `teamMember`,
    az Itemsnél `stocks`).
14. `KanbanBoard::reorder()` egy `findMany()`-vel dolgozik, eager-loadolt
    assignee-kkel.
15. `DatabaseInspector` sorszámai `Cache::remember(60)` mögött.

### P3

17. A beégetett stringek `lang/{en,hu}/admin.php`-be kerültek (új kulcsok:
    `common.id`, `team.discord_*`, `team.group_*`, `cms.*`, `tasks.drag_*`,
    `applications.login_taken*`, `drawing.saved*`, `calendar.event_not_found`,
    `onshape.export_queued*`, `users.temp_skipped*`, `items.log_*`). A maradék
    literálok szándékosak (`'—'` placeholder, URL-minta, `label('')` képoszlop).
18. `$payload` → `$ping` a kanban értesítési ciklusában.
19. `App\Filament\Resources\Cms\TeamMembers` → `…\Resources\TeamMembers`
    (és a groups ugyanígy). Mindkét resource pinneli a régi slugot, így az URL,
    a route-név és a route-névre kulcsolt help-modal változatlan.
20. `PermissionsTest` — az admin-only lista mellé bekerült egy
    `memberReadableRoutesProvider` (items, item-management, item-log, calendar),
    hogy a „member is látja" ezentúl döntés legyen, ne felügyeleti hiba.
21. `RequirePasswordChange` — a `livewire/*` átengedés komponensnévre szűkült
    (profil + értesítés-komponensek, a globális keresés nem). **Emellett kiderült,
    hogy a middleware eddig el sem ért a Livewire-kérésekhez**: a panel
    `authMiddleware`-ében volt, a `/livewire/update` viszont sima `web` route —
    ezért felkerült a `web` csoportra is (`bootstrap/app.php`).
22. Az Onshape GLB export `dispatch()`-csel megy a queue-ra; az eredményt a
    kérő felhasználó a **csengőnél** kapja meg (`sendToDatabase`).

### Tesztek

Új: `AdminAccessGatesTest`, `IssueTempPasswordTest`, `MemberTaskFlowTest`,
`TeamMemberLoginProvisioningTest`, `ProvisionMemberLoginCommandTest`, plusz
bővítés a `PermissionsTest`-ben és a `ProfilePasswordFormTest`-ben.
**244 teszt zöld** (4 skipped). A `phpunit.xml` kapott egy
`memory_limit=512M`-et: az egy processzben futó suite átlépte a 128M-es
alapértéket, és fatalra állt a végén.

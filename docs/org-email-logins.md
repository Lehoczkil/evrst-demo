# EVRST org email logins

How team members get an `@evrst.hu` address, sign in with it, and set their own password
on first login — while every email they receive goes to their private inbox.

Written 2026-09-07, updated 2026-09-08 with the login-only decision (§3), the bulk
onboarding action (§4) and the Hungarian mail templates (§4.1). Against branch
`feature/vue-frontend`; the code is in the working tree but **not committed**.

---

## 1. The model

One address is the identity, a different address is the inbox. That separation is the
whole design: an `@evrst.hu` address is a login the moment we mint it, and it never has
to become a mailbox for the panel to work. Per §3 it deliberately stays that way.

| Column | Value | Role |
| --- | --- | --- |
| `users.email` | `laszlo.lehoczki@evrst.hu` | **The login.** Typed into the Filament sign-in form and into "Forgot password?" |
| `team_members.email` | `laszlo.lehoczki@evrst.hu` | The org address on record. Kept in sync with the login. |
| `team_members.email_private` | `lehoczkilaszlo2002@gmail.com` | Personal inbox. Where mail is **delivered** — the standing arrangement, not a stopgap. |

Which of the two receives mail is one env flag:

```
MAIL_DELIVER_TO_ORG=false   # deliver to email_private, fall back to the login  (the setting we use)
MAIL_DELIVER_TO_ORG=true    # deliver to the org address, fall back to email_private (needs mailboxes first)
```

Everything that sends resolves the recipient through **`User::deliveryEmail()`**. Never
re-derive the preference at a call site — that is how the two drift apart.

Address format, from `App\Support\OrgEmail`:

- roster stores Hungarian order (`Lehoczki László`), the address reads given-name first → `laszlo.lehoczki`
- accents transliterated: `Bába Kíra` → `kira.baba`
- extra given names dropped: `Horváth Márton Antal` → `marton.horvath`
- hyphens collapsed: `Tello-Pálfy Sebastián` → `sebastian.tellopalfy`
- collisions suffixed: a second `laszlo.lehoczki` becomes `laszlo.lehoczki.2`
- domain from `config('mail.org_domain')` / `MAIL_ORG_DOMAIN`, default `evrst.hu`

---

## 2. The 18 roster addresses

| Name | Org login (the login) | Delivery address (the inbox) |
| --- | --- | --- |
| Bihari Bertalan | `bertalan.bihari@evrst.hu` | `biharibertalan@gmail.com` |
| Klabacsek Bálint | `balint.klabacsek@evrst.hu` | `klabacsekbalint@gmail.com` |
| Bihari Balázs | `balazs.bihari@evrst.hu` | `balazs.bihari2@gmail.com` |
| Kincses Márk | `mark.kincses@evrst.hu` | `kincsesmark3@gmail.com` |
| Bába Kíra | `kira.baba@evrst.hu` | `babakira520@gmail.com` |
| Czirják Péter | `peter.czirjak@evrst.hu` | `peterczirjak1998@gmail.com` |
| Kürtösi Simon | `simon.kurtosi@evrst.hu` | `kurtosi.simon@gmail.com` |
| Tello-Pálfy Sebastián | `sebastian.tellopalfy@evrst.hu` | `sebastian.tellopalfy@gmail.com` |
| Kriston Zoltán | `zoltan.kriston@evrst.hu` | `kristonzoli2002@gmail.com` |
| Horváth Márton Antal | `marton.horvath@evrst.hu` | `marton.horvath302@gmail.com` |
| Obsitos Péter | `peter.obsitos@evrst.hu` | `obsitospeti04@gmail.com` |
| Laschek Ádám | `adam.laschek@evrst.hu` | `adam.laschek@gmail.com` |
| Bába Csaba | `csaba.baba@evrst.hu` | `baba.csabi@gmail.com` |
| Hernádi Andre Jozsef | `andre.hernadi@evrst.hu` | `andrehernadi@gmail.com` |
| Nyári György | `gyorgy.nyari@evrst.hu` | **none on file** |
| Mosberger Péter | `peter.mosberger@evrst.hu` | `mosbergerpeti@gmail.com` |
| Lehoczki László | `laszlo.lehoczki@evrst.hu` | `lehoczkilaszlo2002@gmail.com` |
| Som Nemere | `nemere.som@evrst.hu` | **none on file** |

> **Three members left on 2026-09-09** — Lázár Ruben, Kerek Gábor and Bagi Roland.
> They are marked with `team_members.left_at`, which drops them from the public site
> (`TeamController@members` filters `whereNull('left_at')`) while keeping their record,
> activity log and task comments. Their `users` rows still exist, so revoke the login
> separately if that is wanted — note that hard-deleting a user cascades
> `task_comments.user_id` and destroys their comments. They are also removed from
> `TeamSeeder`, so a fresh install never creates them.

Regenerate this list any time without touching the database:

```sh
cd backend && php -r '
require "vendor/autoload.php"; $app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = (new ReflectionClass(Database\Seeders\TeamSeeder::class))->getConstants();
foreach ($c["MEMBERS"] as $m) {
    printf("%-24s %-32s %s\n", $m["name"],
        $m["email"] ?? App\Support\OrgEmail::forName($m["name"]),
        $m["email_private"] ?? "-- none --");
}'
```

> **Two members have no personal address on file** — Nyári György and Som Nemere. There
> is nowhere deliverable to send them, so the bulk action skips them on purpose. See
> §3.2.

---

## 3. Going live

**Decision, 2026-09-08: `@evrst.hu` stays a login only.** No mailboxes, no
aliases, no catch-all at Websupport. `MAIL_DELIVER_TO_ORG` stays `false`
permanently until someone decides otherwise, so every member signs in with
their org address and receives every EVRST email at their private address.

That makes §3.3 the only mandatory step. §3.4 and §3.5 are parked.

### 3.1 Schema

`2026_07_21_000000_assign_org_login_emails.php` backfills existing rows. On the
Hetzner stack you don't run it by hand: `backend/docker/entrypoint.sh` runs
`php artisan migrate --force` on every boot of the `backend` service (the
`queue` and `scheduler` services have `RUN_RELEASE_TASKS=0` so they don't race
it over the shared SQLite file). Locally:

```sh
cd backend && php artisan migrate
```

What it does:

- sets `team_members.email` and `users.email` to the org address
- leaves accounts with **no** linked team member alone — this is what protects
  the break-glass `admin@evrst.test` login
- moves a previous non-stub login (e.g. an old Gmail) into `email_private` when
  that column is empty, so the only deliverable address we had isn't lost
- suffixes (`.2`) rather than colliding on the unique index
- is idempotent; `down()` is a deliberate no-op

On a fresh install it does nothing, because `TeamSeeder` already provisions org
logins. It exists for databases seeded before this change, where the seed-once
volume marker means the seeder won't run again.

### 3.2 Fill in the two missing private addresses

**Nyári György** and **Som Nemere** have `email_private = null` in
`TeamSeeder`. With `MAIL_DELIVER_TO_ORG=false`, `deliveryEmail()` falls back to
their `@evrst.hu` login — which has no mailbox, so mail bounces. The bulk
send-temp-password action (§4) deliberately **skips** them rather than rotating
a password it can't deliver.

Fix either way:

- add the address in the admin: Membership → Team members → *Private email*, or
- hand them their temp password out of band from
  `backend/storage/app/seeded-team-passwords.txt`.

### 3.2b Check for logins minted from a stale name

`OrgEmail` derives the address from `team_members.name`, so a name that was fixed in
`TeamSeeder` *after* the first seed leaves a stale login behind — the seed-once volume
marker means the seeder never re-runs to correct it. Known case: the local dev DB still
holds `Lehocki László` → `laszlo.lehocki@evrst.hu`, while the seeder now says
`Lehoczki László` → `laszlo.lehoczki@evrst.hu`.

Audit any database before sending temp passwords out:

```sh
cd backend && php -r '
require "vendor/autoload.php"; $app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$roster = [];
foreach ((new ReflectionClass(Database\Seeders\TeamSeeder::class))->getConstants()["MEMBERS"] as $m) {
    $roster[App\Support\OrgEmail::forName($m["name"])] = $m["name"];
}
foreach (App\Models\User::whereNotNull("email")->get() as $u) {
    if (str_ends_with($u->email, "@evrst.hu") && ! isset($roster[$u->email])) {
        printf("STALE: %-28s %s\n", $u->name, $u->email);
    }
}'
```

Fix before the address is handed out, not after — once a member has signed in with it,
it is a credential. Either re-run `php artisan db:seed --class=TeamSeeder` (it relinks by
`email_private` / the retired `@evrst.test` stub rather than duplicating) or correct the
name on the team member and use *Generate from name* on the org-email field, which syncs
`users.email` via `EditTeamMember::syncLoginEmail()`.

### 3.3 Point production at Resend  ← the one required step

Resend is already verified on the domain (DKIM + return-path MX are live, see
§5). All that's missing is the key. On the deploy host, in
`backend/.env.production` (the file `compose.yaml` loads into the `backend`,
`queue` and `scheduler` services):

```
MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS="no-reply@evrst.hu"
MAIL_FROM_NAME="EVRST"
MAIL_ORG_DOMAIN=evrst.hu
MAIL_DELIVER_TO_ORG=false
APP_URL=https://evrst.hu
```

`backend/.env.production.example` already ships every one of these except the
key itself. Then recreate the containers so all three services pick up the
change:

```sh
docker compose up -d
```

**`APP_URL` matters more than it looks.** The task notifications are
`ShouldQueue`, so they render in the queue worker with no HTTP request in
scope — `TaskResource::getUrl()` falls back to `config('app.url')`. Get it
wrong and every "Open task" button in every notification email points at
`http://localhost`. The temp-password mail is safer (it's sent with `sendNow`
inside the admin request, and `TeamMemberAccountCreated::loginUrl()` falls back
to `APP_URL . '/admin/login'` if no panel is booted) but there is no reason to
rely on that.

Local dev stays on `MAIL_MAILER=log`, which writes the message — including the
temp password — to `backend/storage/logs/laravel.log`. The Filament actions
surface the difference: on `log` they show a persistent warning saying the
password was logged, not sent.

### 3.4 (Parked) Create the mailboxes at Websupport

Only needed if `@evrst.hu` should ever *receive*. `evrst.hu` MX points at
Websupport (`mx10`/`mx20.websupport.hu`), but the domain has no mail service
attached — the panel shows *"Nincs kapcsolva tárhelyhez"*, so the only mail
feature available is the **Domain kosár** wildcard forwarder under
`Domain átirányítás → Levelezés - Emailek`.

If you do this later, prefer **per-member aliases** forwarding to each private
address over the wildcard. The wildcard funnels all 21 members into one shared
inbox, which is fine for "someone writes to the team" and wrong for temp
passwords. Note that mail sent *to* `@evrst.hu` currently bounces; nothing in
the app cares, because nothing sends there.

### 3.5 (Parked) Flip delivery to the org addresses

Only after §3.4. Then:

```
MAIL_DELIVER_TO_ORG=true
```

No code change, no migration. Verify in the admin: **Membership → Users**, the
*Mail delivered to* column shows the address each user's mail actually goes to,
with a tooltip explaining why.

## 4. How each flow behaves

**First login.** A provisioned user has `password_changed_at = null`.
`App\Http\Middleware\RequirePasswordChange` redirects them to `/admin/profile` from every
other page until they set a password. `ForceChangeProfile` stamps
`password_changed_at` on save. The email field on that page is **read-only for
non-admins** — the login is team-managed identity, not a user preference.

**Forgot password.** Enabled on the login screen via `->passwordReset()` in
`AdminPanelProvider`. The member types their org address; the broker finds the user by
`users.email`; the link is delivered to `deliveryEmail()`. A completed reset fires
`Illuminate\Auth\Events\PasswordReset`, and the listener in `AppServiceProvider` stamps
`password_changed_at` — so a reset also clears the forced-change gate instead of bouncing
the user straight back to the profile page.

**Accepting a new applicant.** `/admin/member-applications/{id}/accept` prefills an
**EVRST login address** derived from the name (editable — a hand-edit sticks even if you
later fix a typo in the name). That address becomes the login and
`team_members.email`; the address from the application form becomes `email_private`. A
temp password is mailed to `deliveryEmail()`.

**Existing member, no login yet.** Team member edit page → **Create login** header
action. Uses whatever is in the member's org email field.

**Changing a member's org address.** Edit the *EVRST email (login)* field on the team
member; `EditTeamMember::syncLoginEmail()` moves `users.email` with it and confirms via a
notification. If another account already holds that address it warns and leaves the login
untouched rather than crashing on the unique index. The field has a **Generate from name**
suffix button; it deliberately does not regenerate live, because rewriting an address
already handed out would lock the member out.

**Resend temp password.** Users table row action — rotates the password, nulls
`password_changed_at`, mails the new one to `deliveryEmail()`.

**Onboarding the whole roster.** Users table → select rows → **Send temp password**
(`UsersTable::sendTempPasswords()`). Same thing as the row action, batched, and it is the
intended way to go live: `TeamSeeder` deliberately does *not* email the passwords it
mints (that would spam on every reseed) — it writes them to
`storage/app/seeded-team-passwords.txt` instead. Behaviour worth knowing:

- Sent with `sendNow`, not queued, so the result notification reports real delivery
  rather than "queued" silence — and it works on a box with no worker running.
- A member with no deliverable address is **skipped, not rotated**. Rotating a password
  we can't deliver would lock them out of the one they have. The result notification
  names who was skipped.
- One failing address doesn't abort the batch; failures are collected and reported.
- On `MAIL_MAILER=log` it says so loudly instead of pretending to have sent.

**What the temp-password mail says.** It has to stand on its own, because it's the only
onboarding instruction a member gets: the sign-in address, the temp password, a button to
the login page, then — deliberately, in this order — that the address is an account name
and *not* a mailbox, that the first sign-in forces a password change, which inbox this
mail arrived at and why, and how "Forgot password?" will behave later. Covered by
`tests/Feature/AccountEmailContentTest.php`.

### 4.1 Language

`User` implements `HasLocalePreference` (returning `users.locale`), so every notification
renders in the language the member picked with the topbar switcher. Copy lives in
`lang/{en,hu}/admin.php` under `mail.*`.

Laravel's own mail chrome — `Regards,`, the "trouble clicking the button" subcopy, and
the whole built-in password-reset email — are JSON translation keys, not `admin.php`
ones, so they come from **`lang/hu.json`**. Without that file a Hungarian member gets
Hungarian body copy wrapped in English framing.

Two fallbacks to keep in mind: a member who has never touched the switcher has
`locale = null`, and the queue worker then renders in `APP_LOCALE` (`en` by default) —
set `APP_LOCALE=hu` in `backend/.env.production` if Hungarian should be the default. And
`Task::statusLabels()` is intentionally hardcoded English (it feeds internal state), so
the notifications look the label up in `admin.tasks.statuses` first and only fall back to
it.

**Re-seeding.** `php artisan db:seed` (or `--class=TeamSeeder`) is safe to re-run: it
matches an existing account by org address, personal address, *or* the retired
`@evrst.test` stub, so it relinks instead of minting duplicates, and it never rotates an
existing password. Freshly provisioned logins are printed as a table and written to
`backend/storage/app/seeded-team-passwords.txt`.

---

## 5. DNS reference (verified 2026-09-07)

```
evrst.hu            MX   10 mx10.websupport.hu / 100 mx20.websupport.hu   ← inbound mail
evrst.hu            TXT  v=spf1 a mx include:_spf.websupport.hu -all
resend._domainkey   TXT  p=MIGf...                                        ← Resend DKIM
send.evrst.hu       MX   10 feedback-smtp.eu-west-1.amazonses.com         ← Resend bounces
```

The root SPF record is `-all` and does **not** include Resend, which looks alarming but is
correct for this setup: Resend uses `send.evrst.hu` as the envelope/return-path domain, so
SPF is evaluated there, and DKIM is signed as `evrst.hu`. DMARC passes on DKIM alignment,
so a `From: no-reply@evrst.hu` is fine. Don't "fix" the root SPF by adding Resend unless
you also move the return path.

Re-check any time:

```sh
dig +short MX evrst.hu; dig +short TXT evrst.hu
dig +short TXT resend._domainkey.evrst.hu; dig +short MX send.evrst.hu
```

---

## 6. Where the code lives

| Path | What |
| --- | --- |
| `backend/app/Support/OrgEmail.php` | The address format. Single source of truth. `forName()`, `localPart()`, `uniqueForName()`, `isTaken()`, `isOrgAddress()`, `domain()`. |
| `backend/app/Models/User.php` | `deliveryEmail()`, `routeNotificationForMail()`, `preferredLocale()`. |
| `backend/config/mail.php` | `org_domain`, `deliver_to_org_addresses`. |
| `backend/database/migrations/2026_07_21_000000_assign_org_login_emails.php` | Backfill. |
| `backend/database/seeders/TeamSeeder.php` | Roster → org logins; `orgEmail()` + `legacyStubEmail()`. |
| `…/Filament/Resources/MemberApplications/Pages/AcceptMemberApplication.php` | Accept flow, org-address field. |
| `…/Filament/Resources/Cms/TeamMembers/Pages/EditTeamMember.php` | `syncLoginEmail()`, Create-login action. |
| `…/Filament/Resources/Cms/TeamMembers/Schemas/TeamMemberForm.php` | Org email field + Generate-from-name. |
| `…/Filament/Resources/Users/Tables/UsersTable.php` | *Login* + *Mail delivered to* columns, Resend temp password (row) + Send temp password (bulk), `isDeliverable()`. |
| `…/Filament/Auth/ForceChangeProfile.php` | First-login password form; email locked for non-admins. |
| `…/Providers/AppServiceProvider.php` | `PasswordReset` → stamps `password_changed_at`. |
| `…/Providers/Filament/AdminPanelProvider.php` | `->passwordReset()`. |
| `backend/app/Notifications/TeamMemberAccountCreated.php` | Temp-password mail; explains the login/inbox split when they differ. |
| `backend/lang/hu.json` | Hungarian for Laravel's own mail chrome + the built-in password-reset email. |
| `backend/lang/{en,hu}/admin.php` | `applications.org_email*`, `team.org_email*`, `team.login_email_*`, `users.login_email*`, `users.notification_email*`, `profile.email_locked`. |

Tests: `backend/tests/Unit/OrgEmailTest.php`,
`backend/tests/Feature/OrgLoginEmailTest.php`,
`backend/tests/Feature/AssignOrgLoginEmailsMigrationTest.php`,
`backend/tests/Feature/AcceptApplicationOrgLoginTest.php`,
`backend/tests/Feature/RouteNotificationForMailTest.php`,
`backend/tests/Feature/BulkTempPasswordTest.php`,
`backend/tests/Feature/AccountEmailContentTest.php`.

```sh
cd backend && php artisan test --filter="OrgEmail|OrgLogin|AssignOrgLoginEmails|AcceptApplicationOrgLogin|RouteNotificationForMail|BulkTempPassword|AccountEmailContent"
```

Full suite was green when this was written: 161 passed, 4 skipped.

---

## 7. Gotchas for later

- **Don't hand-roll the address format.** `OrgEmail` is shared by the seeder, the
  migration and three Filament forms. A second implementation will drift.
- **Don't read `teamMember->email_private` directly** to decide where to send. Use
  `User::deliveryEmail()` or `MAIL_DELIVER_TO_ORG` becomes a lie.
- **Don't redeclare a narrowly-typed `public Model $record`** on a Filament resource Page.
  Livewire assigns the route parameter to any name-matching public property *before*
  `mount()` runs, so `public MemberApplication $record` throws
  `Cannot assign string to property …`. Match Filament's own union
  (`Model | int | string | null`) — `AcceptMemberApplication` shows the pattern.
- **`OrgEmail::uniqueForName()` hits the database**; `forName()` doesn't. The seeder uses
  `forName()` on purpose so its output stays deterministic — a test asserts the 21 roster
  addresses are unique, which is what would otherwise surface as a unique-index crash on
  the next reseed.
- Renaming a member does **not** move their address. That's intentional: the address is a
  credential once issued. Use *Generate from name* to opt in.
- The retired `2026_07_21_000000_repoint_user_login_email_to_private` migration (which
  pointed logins at personal Gmail addresses instead) was replaced before it ever ran.
  If you see it referenced anywhere, it's stale.

---

## 8. Not done / possible follow-ups

- **Inbound mail doesn't exist.** Resend is send-only, and per §3 there is no mailbox
  behind `@evrst.hu` — mail sent *to* an org address bounces. Nothing in the app sends
  there, so nothing breaks, but don't print an org address as a contact address anywhere.
- `email_verified_at` exists on `users` but is unused — no verification flow.
- There is a bulk **Send temp password** action now (§4), but still no bulk
  "provision logins for everyone" — creating the *accounts* is the seeder or the
  per-member **Create login** action.
- Members with no `email_private` can't be mailed at all (§3.2). The bulk action skips
  them silently-but-reported; nothing warns you at the Team member form.
- The mail copy is translated, but `Task::statusLabels()` and the Filament *database*
  notifications rendered at send time freeze whatever locale the recipient had then —
  changing the switcher doesn't retranslate old bell notifications.
- Nothing here is committed yet — expect to review `git diff` before committing, and
  keep it to the small conventional-prefix commits this repo uses.

# Discord értesítések — élesítési teendők

Ez a lap arról szól, mi van még hátra ahhoz, hogy élesen is menjenek a
privát üzenetek. Sorrendben, felülről lefelé — minden lépésnél ott van,
mit kell látnod ahhoz, hogy továbbmehess.

**Hol tartunk (2026-09-15):**

| Lépés | |
| --- | --- |
| 1. Push → deploy | ✅ kész |
| 2. Env a szerverre | ✅ kész — a `--dry-run` lefutása bizonyítja, token és guild id nélkül 403 lenne |
| 3. A három kilépett tag törlése | ✅ kész |
| 4. Hiányzó handle-ök | ⏳ három még hiányzik |
| 5. Snowflake-ek begyűjtése | ⏳ csak `--dry-run` futott, az éles még nem |
| 6. Ellenőrzés | ⏳ |

Kódoldali leírás (hogyan működik, hol van mi):
[`../CLAUDE.md`](../CLAUDE.md) „Discord" bekezdései és
[`../.claude/skills/evrst-backend/SKILL.md`](../.claude/skills/evrst-backend/SKILL.md)
„Discord delivery paths".

---

## A modell három mondatban

Két csatorna fut **egymás mellett**, egyik sem váltja ki a másikat: a
csatorna-webhook viszi a csapat közös naplóját, a bot pedig privátban is
elküldi annak, akiről az üzenet szól.

Akinek nincs snowflake-je a `team_members.discord_id` mezőben, az továbbra
is megkapja a csatornaposztot — vagyis az ID-k begyűjtése **bővítés, nem
átállás**. Nem törik el semmi félúton.

Minden kimenő üzenet egyetlen ponton megy ki: `App\Support\DiscordDelivery`.

---

## 0. Amit ne csinálj

- **Ne seedelj élesen.** A `TeamSeeder` `forceDelete()`-tel indul az egész
  roszteren. Elvesznek a tagfotók (`photo_path`), az alumni-jelölés
  (`left_at`), a bio-k, a pivot előzménye — és aki a panelen keresztül
  került fel, az véglegesen törlődik, miközben **a belépése megmarad**,
  mert a tömeges törlés nem vált ki modell-eseményt. Amit a seedtől
  remélnél (snowflake-ek), azt a `discord:sync-ids` roncsolás nélkül
  megcsinálja.
- **Ne törölj tagot SQL-ből vagy tömeges query-ből.** Ugyanaz a csapda:
  marad egy fiók, ami be tud lépni, de sehol nem látszik. A panelen
  keresztül törölj.
- **Ne írd be kézzel a snowflake-eket.** A sync a Discord-handle alapján
  megtalálja őket. Kézi beírás csak akkor kell, ha a handle nem működik.
- **Ne `docker compose restart`-tal próbáld érvényesíteni az új env-et.**
  Lásd 2. lépés.

---

## 1. Push → deploy

A `main`-re pusholás deployol. A deploy **önmagában nem kapcsol be
semmit**: token nélkül minden pontosan úgy megy, mint eddig — csatornaposzt
igen, DM csendben kimarad.

Egy migráció fut le a boot során
(`2026_09_14_000001_move_about_body_into_home_copy`): a Rólunk → Tartalom
szövegét átmásolja a főoldali copy `manifesto.first` mezőjébe. Nem ír felül
semmit, a régi sort nem törli, és van `down()`-ja.

**Amit látnod kell:** a deploy job zöld, `/up` 200-at ad, és a panelen a
Rólunk csoportból eltűnt a *Tartalom* menüpont, a szöveg pedig megjelent a
**Főoldal szövegei → Küldetés szövege → Első bekezdés** mezőben.

---

## 2. Env a szerverre

A `backend/.env.production`-be a szerveren:

```
DISCORD_BOT_TOKEN=<a bot token a Discord developer portálról>
DISCORD_GUILD_ID=1155161462214508605
```

Két csapda:

- A `compose.yaml` szerint a **`backend`, a `queue` és a `scheduler`**
  ugyanabból az `env_file`-ból olvas. A DM-eket ténylegesen a **`queue`
  konténer** küldi (a jobok `ShouldQueue`), szóval neki is látnia kell.
- **`docker compose restart` nem elég.** Az `env_file` a konténer
  *létrehozásakor* kerül be; a restart csak a processzt indítja újra a régi
  környezettel. Kell:

```sh
docker compose up -d
```

Ez újra is építi a config cache-t (az entrypoint prodban `config:cache`-el).

**Amit látnod kell:**

```sh
docker compose exec backend php artisan mail:doctor --no-api
```

a `DISCORD_BOT_TOKEN` sorban `set`.

---

## 3. A három kilépett tag törlése

Lázár Ruben, Kerek Gábor, Bagi Roland — **a panelen keresztül**
(Csapat → Csapattagok, sor- vagy tömeges törlés).

Miért számít: a `TeamMember::deleted` modell-esemény futtatja a
`MemberLogin::revoke()`-ot, ami a belépésüket is törli. Ez négy törlési
úton működik (sorművelet, tömeges művelet, szerkesztőoldal fejlécgomb,
shell) — de csak modellen keresztül.

**Figyelem:** a `revoke()` szándékosan **nem töröl admin fiókot** (és a
sajátodat sem). Nézd meg törlés előtt a szerepüket: ha bármelyikük admin,
a tagsor eltűnik, de a fiók megmarad, és kézzel kell törölni a
**Membership → Felhasználók** alatt.

**Amit látnod kell:** a három név eltűnt a névsorból, és a Felhasználók
táblában sincs meg a `ruben.lazar@`, `gabor.kerek@`, `roland.bagi@` fiók.

---

## 4. Hiányzó Discord-handle-ök pótlása

> **A seeder nem mérce.** A `TeamSeeder` élesen kötetenként egyszer fut
> (`.seeded` marker), tehát amit oda beírunk, az egy már működő éles
> adatbázisba **soha nem jut el**. Attól, hogy egy tagnak a seederben van
> handle-je, élesen még üres lehet a mezője — és ez nem is látszik, amíg
> a sync `no match`-et nem ír rá. Ezen a listán tehát **nem a seedert kell
> nézni, hanem a panelt**: futtasd le előbb az 5. lépés `--dry-run`-ját,
> és amit az `no match`-nek jelöl, azt pótold.

A **Csapattagok → szerkesztés → Discord felhasználónév** mezőbe:

| Tag | Handle |
| --- | --- |
| Szarka Marcell | `marcell0202` |
| Tiboldi Csongor | `csoncso` |
| Penc Máté | `mattaiusz` |
| Stirling Andras | `stirlin6` |
| Veres Dávid | `_red_leader` |

A **Discord snowflake** mezőt hagyd üresen — a következő lépés tölti ki.

Két dolog, ami elsőre adathibának néz ki, de nem az:

- **A handle körüli szóköz nem baj.** A sync `trim()`-el, szóval egy
  bemásolt ` marcell0202` is párosul. Kiszedni azért érdemes, mert a mező
  más olvasói nem feltétlenül trimmelnek.
- **Egy helyes handle is adhat `no match`-et**, ha az illető időközben
  kilépett a Discord szerverről vagy átnevezte magát. Ilyenkor nem a
  rosztert kell javítani, hanem megkérdezni az embert. (Laschek Ádám
  pontosan ez volt 2026-09-15-én: az `adamlasy` handle jó volt, a fiók
  aznap tűnt el a szerverről.)

---

## 5. Snowflake-ek begyűjtése

```sh
docker compose exec backend php artisan discord:sync-ids --dry-run
docker compose exec backend php artisan discord:sync-ids
```

A parancs a guild taglistáját párosítja a tárolt `discord_username`-hez
(username → global name → szerveres becenév sorrendben). Idempotens: csak
azt írja, ami hiányzik vagy elcsúszott. Nem enged két roszter-sort ugyanarra
a Discord-fiókra mutatni, és a kilépett tagokat békén hagyja.

Kapcsolók: `--dry-run` (csak kiírja, mit tenne), `--nicks` (a szerveres
megjelenített nevet is frissíti), `--guild=<id>` (felülírja a
`DISCORD_GUILD_ID`-t).

**Ha 403-at kap:** a botnál nincs bekapcsolva a **Server Members Intent**
a Discord developer portálon (Bot → Privileged Gateway Intents). Ez
kizárólag a taglista olvasásához kell — a DM-küldéshez nem, és pont ezért
könnyű kihagyni.

**Amit látnod kell:** a dry run minden sorban `up to date` vagy
`would set id`, és a végén a `no match` sorok száma annyi, ahány tagnak
tényleg nincs handle-je.

---

## 6. Ellenőrzés

```sh
docker compose exec backend php artisan mail:doctor --no-api
```

A `discord_id coverage` sor megmondja, hányan kapnak privát üzenetet.
Ez **advisory** sor, nem buktatja a pre-flightot: a DM kiegészítés a
csatornaposzt mellé, és egy zöld `mail:doctor` a levélküldésre jogosít,
nem a DM-re.

Valódi próba: hozz létre egy naptáreseményt a panelen. Az mindenkinek megy
— egy csatornaposzt, plusz privát üzenet minden snowflake-kel rendelkező
tagnak, kivéve téged (aki létrehozta, azt kihagyjuk).

---

## Amit a panelből nem lehet megoldani

**Mindenkinél be kell legyen kapcsolva a Discordban, hogy szervertagoktól
fogad privát üzenetet.** Ha valakinél ki van kapcsolva, a bot 403-at kap,
a job csendben feladja — szándékosan nem próbálkozik újra, mert azon a
retry nem segít —, és az illető csak a csatornaposztot látja.

Ez fejenként dől el, kívülről nem látszik, és ez fogja adni a „nekem miért
nem jön?" kérdéseket. Érdemes egyszer végigkérdezni a csapatot:
*Felhasználói beállítások → Adatvédelem és biztonság → Közvetlen üzenetek
fogadása szervertagoktól.*

---

## Ismert hiányok

Állapot 2026-09-15, az éles `--dry-run` alapján: **23 tagból 21** kaphat
privát üzenetet, miután a 4. lépés három handle-je bekerült.

- **Dr. Mosberger Péternek** semmilyen Discord-adata nincs (se handle, se
  snowflake), és a szerveren sincs hozzá illeszthető fiók — ő nincs bent a
  Discordon. Amíg nem lép be, csak a csatornaposztot látja.
- **Laschek Ádám** handle-je (`adamlasy`) helyes, de az a fiók 2026-09-15-én
  kilépett a szerverről. Nem adathiba: vagy visszalép, vagy meg kell tudni
  az új felhasználónevét.
- **`szaboistvanphd`** (id `1447664454434033674`) bent van a szerveren, de
  nem tartozik hozzá roszter-sor. Konzulens? Mentor? Tisztázandó.

A lista ellenőrzése egyetlen parancs, bármikor:

```sh
docker compose exec backend php artisan discord:sync-ids --dry-run
```

---

## Miről megy értesítés

| Esemény | Honnan | Kihez ér el |
| --- | --- | --- |
| Új jelentkezés | join-us űrlap | csatorna |
| Jelentkezés elfogadva / elutasítva | Jelentkezések | csatorna |
| Új CMS esemény | Események | csatorna |
| Naptáresemény **létrehozva / módosítva / törölve** | Naptár | csatorna + DM az egész roszternek |
| Taskhoz rendelés | Task létrehozás/szerkesztés | csatorna + DM az érintettnek |
| Task státuszváltás | Task szerkesztés, kanban | csatorna + DM a figyelőknek |
| Új komment | Task kommentek | csatorna + DM a figyelőknek |

A naptárnál a **módosítást** csak akkor jelentjük, ha tényleg változott
valami érdemi: a változatlan újramentés és a szín-only szerkesztés néma
marad. A módosítót mindig kihagyjuk — ő épp most csinálta.

DM-ben ugyanaz az embed megy, mint a csatornába, de a szöveg elejéről
lemarad az `@`-jelölés: a kézbesítés már megcímezte.

---

## Ha valami nem megy

| Tünet | Hol nézd |
| --- | --- |
| Semmi nem érkezik, se csatorna, se DM | Fut-e a `queue` konténer? A jobok `ShouldQueue`. |
| Csatornába megy, DM-be nem | `DISCORD_BOT_TOKEN` a `queue` konténerben is? (`up -d`, nem `restart`) |
| Egy embernek nem megy, másnak igen | Van snowflake-je? Ha igen: nála a DM-fogadás. |
| `discord:sync-ids` 403 | Server Members Intent a developer portálon |
| „Open task" linkek localhostra mutatnak | `APP_URL` a `.env.production`-ben — a queue workerben nincs request scope |

Naplók: a sikertelen DM és webhook `Log::warning`-ot ír a
`storage/logs/laravel.log`-ba, a `reference` mezővel együtt
(`task:assign:12:5`, `calendar-event:update:8`, …).

---

## Hol van a kód

| | |
| --- | --- |
| `App\Support\DiscordDelivery` | az egyetlen kijárat — `toRecipient()`, `announce()`, `toChannel()`, `audience()` |
| `App\Support\DiscordPayloads` | embed-építők, `content` + `dm_content`, `mention()`, `snowflakeFor()` |
| `App\Jobs\PostDiscordWebhook` | csatorna |
| `App\Jobs\SendDiscordDirectMessage` | privát üzenet |
| `App\Services\DiscordBot` | REST wrapper (DM-csatorna nyitás, küldés, taglista) |
| `App\Concerns\HandlesDiscordRateLimit` | 429 → `release()` a Discord által kért időre |
| `App\Console\Commands\SyncDiscordIds` | `discord:sync-ids` |

Szerződéses tesztek: `DiscordDualDeliveryTest`, `DiscordRateLimitTest`,
`SyncDiscordIdsCommandTest`, `SendDiscordDirectMessageJobTest`,
`CalendarEventDiscordRoutingTest`.

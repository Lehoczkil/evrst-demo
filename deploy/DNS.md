# Pointing your domain at the Hetzner server (DNS at your registrar)

This is the detailed version of [step 2 of the Hetzner runbook](./HETZNER.md#2-dns).
It assumes you keep DNS **where the domain is registered** (your `.hu` registrar,
Namecheap, GoDaddy, Porkbun, OVH, …) and just add records — **no nameserver
change**. The goal is to make these resolve to your server:

```
yourdomain.hu        →  SERVER_IP   (the SPA + admin + API, behind Caddy)
www.yourdomain.hu    →  SERVER_IP   (optional)
```

Once DNS resolves and ports 80/443 are open, Caddy auto-fetches a Let's Encrypt
certificate for whatever you put in `SITE_ADDRESS` — there is nothing to upload
or configure on the TLS side. DNS is the only thing standing between you and a
working `https://yourdomain.hu`.

> **The one rule that trips people up:** Caddy only serves and gets a cert for
> the hostname(s) in `SITE_ADDRESS` (repo-root `.env`). A DNS record for `www`
> that isn't also in `SITE_ADDRESS` will resolve but return a TLS error. Keep
> the two in sync — see [§4](#4-the-www-decision--keep-dns-and-site_address-in-sync).

---

## Contents

1. [Get your server's IP addresses](#1-get-your-servers-ip-addresses)
2. [The records you need (TL;DR)](#2-the-records-you-need-tldr)
3. [Add the records at your registrar](#3-add-the-records-at-your-registrar)
4. [The `www` decision + keeping DNS and `SITE_ADDRESS` in sync](#4-the-www-decision--keep-dns-and-site_address-in-sync)
5. [IPv6 (AAAA): only if it actually works](#5-ipv6-aaaa-only-if-it-actually-works)
6. [CAA: don't accidentally block Let's Encrypt](#6-caa-dont-accidentally-block-lets-encrypt)
7. [TTL strategy (especially if the domain is already live)](#7-ttl-strategy)
8. [Verify it propagated](#8-verify-it-propagated)
9. [How this lines up with deploying](#9-how-this-lines-up-with-deploying)
10. [Registrar quick-notes](#10-registrar-quick-notes)
11. [Troubleshooting](#11-troubleshooting)
12. [Mini-glossary](#12-mini-glossary)

---

## 1. Get your server's IP addresses

In the Hetzner Console (<https://console.hetzner.com>): **Servers → `evrst-prod`**.
The header / **Networking** panel shows:

- **Public IPv4** — e.g. `203.0.113.7`. This is `SERVER_IP` below.
- **Public IPv6** — usually shown as a `/64` block like `2a01:4f8:c17:abcd::/64`.
  The address your server actually answers on is the **`::1` host in that block**,
  i.e. `2a01:4f8:c17:abcd::1`. That's `SERVER_IPV6` below. (More on whether to
  bother with IPv6 in [§5](#5-ipv6-aaaa-only-if-it-actually-works).)

Double-check the v4 from your Mac (the box answers ICMP by default):

```sh
ping -c1 203.0.113.7
```

---

## 2. The records you need (TL;DR)

For **apex + www**, both pointing straight at the box:

| Type | Name (host) | Value / target | TTL |
|------|-------------|----------------|-----|
| `A` | `@` (apex / root) | `SERVER_IP` | 300 (raise later) |
| `A` | `www` | `SERVER_IP` | 300 |
| `AAAA` | `@` | `SERVER_IPV6` | 300 | *(optional — see §5)* |
| `AAAA` | `www` | `SERVER_IPV6` | 300 | *(optional)* |

That's the whole job. No `CNAME`, no SRV, no nameserver change. You point A
records at a raw IP, so none of the registrar quirks around "CNAME at the apex"
apply here.

> **Apex-only is also fine.** If you don't want `www`, create just the two `@`
> rows (A, and optionally AAAA) and set `SITE_ADDRESS=yourdomain.hu`. Skip §4's
> www handling entirely.

---

## 3. Add the records at your registrar

The wording differs per provider, but every DNS editor has the same fields. Look
for **"DNS"**, **"DNS Zone"**, **"Advanced DNS"**, **"Manage DNS"**, or
**"Nameserver / Records"** in the domain's control panel.

**Field-by-field:**

- **Type** → `A` (IPv4). One row per hostname.
- **Name / Host / Hostname** → this is the part *before* your domain:
  - **apex** (the bare `yourdomain.hu`): most panels want `@`. Some want the field
    **left blank**, and a few want the **full domain typed out** (`yourdomain.hu`).
    When unsure, `@` is the common case; if the panel rejects it, try blank.
  - **www**: type just `www` (the panel appends the domain → `www.yourdomain.hu`).
    Do **not** type `www.yourdomain.hu` unless the panel shows full FQDNs in
    existing rows.
- **Value / Points to / Target / Data** → the IP, e.g. `203.0.113.7`. No `http://`,
  no trailing dot, no port.
- **TTL** → set **300 (5 min)** for now so mistakes are cheap to fix; raise to
  3600+ once it's confirmed working ([§7](#7-ttl-strategy)).
- **Proxy / "DNS only" toggle** → if you ever see one (Cloudflare-style orange
  cloud), this guide assumes **DNS-only / unproxied**. A proxy in front changes
  how TLS works and isn't what this stack expects.

**Remove conflicting records first.** Fresh domains and parked domains often ship
with junk that will fight your new records:

- An existing `A @` pointing at a registrar **parking page** → edit it to your IP
  (or delete + recreate). Two `A @` rows = round-robin to the wrong place half the time.
- A `CNAME www` pointing at a parking host → delete it; you can't have both a
  `CNAME` and an `A`/`AAAA` on the same name.
- A wildcard `A *` pointing somewhere stale → remove or repoint.
- Leftover `AAAA` records pointing at an old host → delete them unless you're
  deliberately setting IPv6 ([§5](#5-ipv6-aaaa-only-if-it-actually-works)). A
  stale `AAAA` is a classic "works for some people, not others" bug.

Leave `MX`, `TXT` (SPF/DKIM/verification), and `NS` records alone — they're about
email and delegation, not your web server ([§11](#11-troubleshooting) has a note
on email).

Save / "Apply changes."

---

## 4. The `www` decision + keep DNS and `SITE_ADDRESS` in sync

`SITE_ADDRESS` (in the repo-root `.env`) is the list of hostnames Caddy serves and
requests certs for. **Every public hostname must appear in both DNS and
`SITE_ADDRESS`,** or you get a cert error on the missing one.

Pick one:

**A) Serve both apex and www (simplest).** Both URLs work and show the site.

- DNS: `A @` + `A www` (from §2).
- `.env`: `SITE_ADDRESS="yourdomain.hu www.yourdomain.hu"`  ← note the quotes.

**B) Apex only.**

- DNS: `A @` only.
- `.env`: `SITE_ADDRESS=yourdomain.hu`

**C) Canonical apex, redirect www → apex (nicest, one extra Caddy block).**
`www.yourdomain.hu` 301-redirects to the bare domain so you don't serve duplicate
content on two hostnames.

- DNS: `A @` + `A www`.
- `.env`: `SITE_ADDRESS="yourdomain.hu www.yourdomain.hu"` (Caddy still needs a
  cert for `www` to be able to redirect it over HTTPS).
- [`Caddyfile`](./Caddyfile): add a redirect block **above** the main site block.
  Caddy can't expand `$SITE_ADDRESS` here, so name the host explicitly:

  ```caddyfile
  www.yourdomain.hu {
      redir https://yourdomain.hu{uri} permanent
  }
  ```

  The Caddyfile is bind-mounted (`./deploy/Caddyfile`), so after editing just
  `docker compose restart web` — no rebuild.

> After **any** `SITE_ADDRESS` change you must recreate the `web` container so it
> picks up the new env and requests the new cert:
> `docker compose up -d web` (or `--build` if you also changed the Dockerfile).

---

## 5. IPv6 (AAAA): only if it actually works

IPv6 is **optional** and easy to get subtly wrong. Docker's published ports
(`80:80`, `443:443` in `compose.yaml`) bind IPv4 by default; forwarding inbound
IPv6 to the container requires Docker's IPv6 support to be enabled on the host.
If you publish an `AAAA` record but the box doesn't actually forward v6 to Caddy,
**IPv6-capable clients will try v6 first and hang/fail**, while IPv4-only clients
work fine — a maddening "it's down for me but not you" bug.

Recommendation:

- **Starting out / unsure:** skip `AAAA` entirely. IPv4 is enough; nothing about
  the stack needs v6.
- **Want IPv6:** confirm the host forwards it before adding the DNS record. After
  the stack is up, from the **server**:
  ```sh
  curl -6 -I https://yourdomain.hu     # only meaningful once AAAA exists; or test by IP
  ```
  Better: from an external IPv6-capable network, `curl -6 -I https://[SERVER_IPV6]/up`.
  Only add the `AAAA` rows once that returns a real response. (Enabling Docker
  IPv6 is out of scope here — if `curl -6` to the host fails, leave `AAAA` off.)

---

## 6. CAA: don't accidentally block Let's Encrypt

A **CAA** record restricts which certificate authorities may issue for your
domain. If your zone has **no** CAA record, any CA (including Let's Encrypt) can
issue — that's the common case and you don't need to do anything.

But some registrars add a restrictive CAA on new domains. If cert issuance fails
and the Caddy log mentions CAA, check it:

```sh
dig +short CAA yourdomain.hu
```

If it returns entries that **don't** include `letsencrypt.org`, add one (Caddy
uses Let's Encrypt, with ZeroSSL as fallback):

| Type | Name | Value |
|------|------|-------|
| `CAA` | `@` | `0 issue "letsencrypt.org"` |
| `CAA` | `@` | `0 issue "pki.goog"` *(optional — ZeroSSL/Google fallback)* |

When no CAA exists at all, **don't add one** — it's an extra thing to get wrong.

---

## 7. TTL strategy

TTL is how long resolvers cache a record. It matters in two situations:

- **Brand-new / parked domain** → nobody's caching anything yet. Set `300` so
  fix-ups are fast, deploy, confirm, then raise to `3600` (1h) for stability.
- **Domain already serving a live site elsewhere** (you're migrating) → **lower
  the TTL to 300 a day *before* you cut over**, while the old IP is still in the
  record. That way when you flip to `SERVER_IP`, the world picks it up in minutes
  instead of hours. Flip the value, confirm, then raise TTL back up.

You can't retroactively shorten a TTL that's already been handed out — the lower
value only helps for lookups that happen *after* you set it.

---

## 8. Verify it propagated

From your Mac. The apex must return your server's IPv4:

```sh
dig +short A yourdomain.hu          # → 203.0.113.7
dig +short A www.yourdomain.hu      # → 203.0.113.7  (if you added www)
dig +short AAAA yourdomain.hu       # → empty unless you set AAAA (§5)
```

Don't trust a single resolver — your Mac may have a stale cache. Ask public
resolvers directly:

```sh
dig +short @1.1.1.1 yourdomain.hu   # Cloudflare
dig +short @8.8.8.8 yourdomain.hu   # Google
```

When all of them show `SERVER_IP`, you're propagated. For a world map of
propagation, paste the domain into <https://www.whatsmydns.net/> (select `A`).

> **Gotcha:** `ping yourdomain.hu` / browser tabs use the **OS cache**, which
> honours the *old* TTL. `dig @1.1.1.1` bypasses that and tells the truth. If
> `dig @1.1.1.1` is correct but your browser still hits the old IP, flush the OS
> cache (macOS: `sudo dscacheutil -flushcache; sudo killall -HUP mDNSResponder`)
> or just wait out the TTL.

---

## 9. How this lines up with deploying

DNS and the cert are coupled: **Caddy can't get a certificate until the hostname
resolves to the box and 80/443 are reachable.** Two safe orderings:

- **DNS first (recommended):** add the records (§3), wait until §8 is green, set
  `SITE_ADDRESS`, then `docker compose up -d --build`. Caddy gets the cert on the
  first try.
- **Deploy first, DNS after:** the stack comes up fine; Caddy just keeps retrying
  cert issuance and **succeeds automatically within a minute or two of DNS going
  live** — no restart needed. Watch it:
  ```sh
  docker compose logs -f web      # look for "certificate obtained successfully"
  ```

Either way, the firewall from [Hetzner step 1d](./HETZNER.md#1-hetzner-console)
must allow inbound **80 and 443** — Let's Encrypt validates over port 80.

> **No domain yet / want to test now?** Use **sslip.io**: `SITE_ADDRESS` of
> `203-0-113-7.sslip.io` (your IP, dashes for dots) auto-resolves to that IP and
> Caddy gets a real cert for it. No DNS records, no registrar. Switch to the real
> domain later by editing DNS + `SITE_ADDRESS` and `docker compose up -d web`.

---

## 10. Registrar quick-notes

Same A-record-at-an-IP everywhere; only the menu names differ.

**Hungarian (`.hu`) registrars**

- **Rackforest / Nethely / EZIT / Tárhelypark** — control panel → domain →
  **"DNS zóna" / "DNS beállítások"**. "Típus" = `A`, "Név / Aldomain" = `@` (or
  blank) for apex and `www` for www, "Cél / Érték" = the IP, "TTL" = 300. Many
  `.hu` panels list the apex as the domain itself rather than `@` — if `@` is
  rejected, leave the name blank or type the full domain.
- **Forpsi / BlazeArts** — **"DNS záznamy / DNS records"**, same fields.
- Note: many `.hu` resellers run DNS on the registry's nameservers; you're editing
  the **zone**, not switching nameservers — exactly the "current registrar" path.

**International**

- **Namecheap** — Domain List → **Manage → Advanced DNS → Add New Record**. Apex
  host is `@`; www host is `www`. Watch for the default **"CNAME www → parkingpage"**
  and the **URL-redirect / parking** rows — delete them.
- **GoDaddy** — **Domain → DNS → Manify / Records**. Apex is `@`. GoDaddy adds a
  parked `A @` and a `CNAME www` by default — edit the `A` to your IP and delete
  the `CNAME www`, then add `A www`.
- **Porkbun** — **Details → DNS Records**. Clean editor; host blank = apex.
- **OVH** — **Web Cloud → Domain → DNS Zone**. Apex = leave subdomain blank.
- **Squarespace (ex-Google Domains)** — **DNS → DNS settings → custom records**.
  Host `@` for apex.
- **Cloudflare** (if your domain happens to use it as registrar/DNS) — add `A @`
  and `A www`, but set the proxy toggle to **"DNS only" (grey cloud)** for this
  stack. Orange-cloud proxying terminates TLS at Cloudflare and changes the
  whole cert story — out of scope here.

---

## 11. Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| `dig +short yourdomain.hu` is **empty** | Record not saved, wrong "Name" field (try `@` vs blank vs full domain), or you edited a zone on nameservers the domain doesn't actually use. Confirm the domain's nameservers (`dig +short NS yourdomain.hu`) are the registrar's. |
| `dig` shows the **old / parking IP** | A leftover `A @` or wildcard still points there, or you're seeing a cached answer — re-check with `dig @1.1.1.1`. Remove the stale record. |
| **Browser still hits old IP, but `dig @1.1.1.1` is correct** | OS/browser cache honouring the old TTL. Flush DNS (see §8 gotcha) or wait out the TTL. |
| **Cert not issued / TLS warning** | DNS not propagated yet (§8), port 80/443 blocked (Hetzner firewall, §1d of the runbook), or a CAA record blocking Let's Encrypt (§6). `docker compose logs web`. |
| **`www` errors but apex works (or vice-versa)** | The failing hostname is in DNS but **not** in `SITE_ADDRESS`, so Caddy has no cert for it. Add it to `SITE_ADDRESS` and `docker compose up -d web` (§4). |
| **Works on phone (4G), broken on home wifi (or reverse)** | Almost always a bad/stale `AAAA` — one network uses IPv6, the other v4. Remove `AAAA` unless v6 is confirmed (§5). |
| **`NXDOMAIN` for `www` only** | You created `A @` but forgot `A www` (or it's a `CNAME` colliding with something). Add `A www`. |
| **Email stopped after the change** | You deleted/overwrote an `MX` or SPF/DKIM `TXT` record. Web records (`A`/`AAAA`) are independent of `MX` — restore the mail records. Outbound app mail uses Resend and is unaffected by these A records, but Resend's own domain verification needs the SPF/DKIM `TXT` records it gives you. |

---

## 12. Mini-glossary

- **A record** — maps a hostname to an IPv4 address. This is the one that does the work.
- **AAAA record** — same, for IPv6.
- **Apex / root / `@`** — the bare domain (`yourdomain.hu`) with no subdomain.
- **CNAME** — an alias from one name to another *name* (not an IP). Not used here
  because we point straight at an IP; also illegal at the apex on most registrars.
- **TTL** — seconds a resolver may cache a record before re-asking. Lower = faster
  changes, slightly more lookups.
- **CAA** — restricts which certificate authorities may issue certs for the domain.
- **Propagation** — the (TTL-bounded) wait for resolvers worldwide to drop the old
  cached answer and pick up your new record.
- **`SITE_ADDRESS`** — repo-root `.env` var listing the hostname(s) Caddy serves and
  gets TLS certs for. Must match your public DNS names.

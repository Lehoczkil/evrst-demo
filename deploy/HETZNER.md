# Deploying EVRST to a Hetzner Cloud server (from zero)

A complete, copy-paste runbook: provision a Hetzner Cloud VM, put the code on it,
and bring up the single-domain stack (Vue SPA + Laravel/Filament admin behind
Caddy, with auto-HTTPS). Architecture and the compose files are documented in
[`README.md`](./README.md) — this is the step-by-step.

**What you'll end up with**

```
https://yourdomain.hu/        → Vue SPA
https://yourdomain.hu/admin   → Filament admin
https://yourdomain.hu/api     → Laravel API
```

One ~€5/month VM runs everything. TLS is automatic (Let's Encrypt via Caddy).
SQLite + uploads live on a Docker volume and survive redeploys.

---

## Contents

0. [Before you start](#0-before-you-start)
1. [Hetzner Console: project + SSH key + server + firewall](#1-hetzner-console)
2. [Point your domain (DNS)](#2-dns)
3. [First login + prepare the server](#3-prepare-the-server)
4. [Get the code onto the server](#4-get-the-code)
5. [Configure environment](#5-configure)
6. [Deploy](#6-deploy)
7. [Verify + first-login security](#7-verify)
8. [Day-2 operations](#8-day-2-operations)
9. [Troubleshooting](#9-troubleshooting)

> **Two different SSH keys are involved — don't mix them up:**
> - **Your key** (on your Mac) → uploaded to Hetzner → lets *you* SSH into the server (steps 1 & 3).
> - **A deploy key** (generated *on the server*) → added to GitHub read-only → lets the *server* clone the private repo (step 4).

---

## 0. Before you start

You need:

- [x] A Hetzner Cloud account (done).
- [ ] An SSH key on your Mac. Check with `ls ~/.ssh/id_ed25519.pub`; if missing:
  ```sh
  ssh-keygen -t ed25519 -C "evrst-hetzner"
  ```
- [ ] A domain you control (e.g. `evrst.hu`). **No domain yet?** You can still do the whole thing and test over HTTPS using `sslip.io` — see the note in step 2.
- [ ] ~30 minutes.

---

## 1. Hetzner Console

Everything here is in the web console at **<https://console.hetzner.com>**.

### 1a. Create a project
Top-left project switcher → **+ New Project** → name it `evrst` → open it.

### 1b. Add your SSH key
**Security → SSH Keys → Add SSH Key.** Paste the output of (run on your Mac):
```sh
cat ~/.ssh/id_ed25519.pub
```
Name it `my-mac`.

### 1c. Create the server
**Servers → Add Server:**

| Field | Choose |
|---|---|
| **Location** | **Nuremberg** or **Falkenstein** (Germany — closest to Hungary) |
| **Image** | **Ubuntu 24.04** |
| **Type** | **Shared vCPU → CX22** (2 vCPU / 4 GB / 40 GB, ~€3.79/mo). *CAX11 (Arm, same price) also works — all our images are multi-arch.* |
| **Networking** | leave **Public IPv4 + IPv6** enabled |
| **SSH Keys** | tick `my-mac` |
| **Backups** | enable (optional, +20% — recommended) |
| **Name** | `evrst-prod` |

Click **Create & Buy now**. After ~30s you get a **public IPv4** — note it (referred to below as `SERVER_IP`).

### 1d. Firewall
**Firewalls → Create Firewall** → add **inbound** rules, then apply it to `evrst-prod`:

| Direction | Protocol | Port | Source |
|---|---|---|---|
| Inbound | TCP | 22 | your IP (or `0.0.0.0/0`, `::/0`) |
| Inbound | TCP | 80 | `0.0.0.0/0`, `::/0` |
| Inbound | TCP | 443 | `0.0.0.0/0`, `::/0` |

(Outbound: leave the default "allow all".) Using the Hetzner Cloud Firewall means
you can't accidentally lock yourself out the way a misconfigured `ufw` can.

---

## 2. DNS

At your domain registrar (or Hetzner DNS / Cloudflare), create records pointing at the server:

| Type | Name | Value |
|---|---|---|
| A | `@` (or `evrst.hu`) | `SERVER_IP` |
| AAAA | `@` | the server's IPv6 (optional) |
| A | `www` | `SERVER_IP` (optional) |

Check it resolves before deploying (cert issuance depends on it):
```sh
dig +short yourdomain.hu      # should print SERVER_IP
```

> **No domain yet?** Skip DNS and use **sslip.io** for testing: a hostname like
> `<SERVER_IP-with-dashes>.sslip.io` (e.g. `203-0-113-7.sslip.io`) auto-resolves to
> that IP, and Caddy *can* get a real Let's Encrypt cert for it. Use it as
> `SITE_ADDRESS` in step 5, then switch to your real domain later.

---

## 3. Prepare the server

SSH in as root (from your Mac):
```sh
  ssh root@SERVER_IP
``

### 3a. A non-root deploy user (recommended)
```sh
adduser --disabled-password --gecos "" deploy
usermod -aG sudo deploy
install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
cp ~/.ssh/authorized_keys /home/deploy/.ssh/authorized_keys
chown deploy:deploy /home/deploy/.ssh/authorized_keys
```
From now on you can `ssh deploy@SERVER_IP`.

### 3b. Add 2 GB swap
The 4 GB box is plenty to *run* the stack, but building the images (composer +
PHPUnit, and the Vite/vue-tsc SPA build) is memory-hungry. Swap prevents OOM kills:
```sh
fallocate -l 2G /swapfile && chmod 600 /swapfile
mkswap /swapfile && swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

### 3c. Install Docker
```sh
curl -fsSL https://get.docker.com | sh
usermod -aG docker deploy          # run docker without sudo
apt-get install -y git
```
Log out and back in as the deploy user so the docker group applies:
```sh
exit
ssh deploy@SERVER_IP
docker version && docker compose version    # both should print, no error
```

---

## 4. Get the code

The repo is private, so give the server a **read-only deploy key**.

On the server (as `deploy`):
```sh
ssh-keygen -t ed25519 -C "evrst-server-deploy" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
```
Copy that public key, then in GitHub:
**repo → Settings → Deploy keys → Add deploy key** → paste it → **leave "Allow write access" unchecked** → Add.

Now clone:
```sh
ssh -o StrictHostKeyChecking=accept-new -T git@github.com   # trust github once (prints a greeting)
git clone git@github.com:Lehoczkil/evrst-demo.git
cd evrst-demo
git checkout feature/vue-frontend    # or main, once the deploy scaffold is merged
```

---

## 5. Configure

Two env files. From the repo root (`~/evrst-demo`):

### 5a. Backend secrets
```sh
cp backend/.env.production.example backend/.env.production
nano backend/.env.production
```
Set at least:
- `APP_KEY=` — generate one (no PHP needed on the host):
  ```sh
  echo "base64:$(openssl rand -base64 32)"
  ```
  Paste the whole `base64:…` string.
- `APP_URL=https://yourdomain.hu` (and `ADMIN_URL` auto-follows).
- `MAIL_MAILER=resend` + `RESEND_API_KEY=…` so temp-password / application emails
  actually send (free tier at <https://resend.com>). Leave as-is to skip email for now.

### 5b. Domain for Caddy
```sh
cp .env.example .env
nano .env        # set SITE_ADDRESS=yourdomain.hu   (or the sslip.io host)
```

### 5c. (Recommended) start with a clean database
The backend image ships the committed dev SQLite. For a fresh production DB
(seeded only by the seeders — roles, permissions, the admin user), exclude it
**before the first build**:
```sh
echo 'database/*.sqlite' >> backend/.dockerignore
```

---

## 6. Deploy

```sh
docker compose up -d --build
```

What happens:
- The **backend image runs the PHP test suite during build** — a failing suite
  aborts the deploy on purpose (bad code never replaces a running prod). First
  build takes a few minutes (composer + image pulls + bun build).
- On boot the backend creates the SQLite DB on the `/persistent` volume, runs
  migrations + seeders, and caches config/routes/views.
- **Caddy requests a Let's Encrypt certificate** for `SITE_ADDRESS` as soon as
  DNS resolves and ports 80/443 are reachable.

Watch it come up:
```sh
docker compose ps
docker compose logs -f backend     # Ctrl-C to stop following
docker compose logs -f web         # look for "certificate obtained successfully"
```

---

## 7. Verify

```sh
curl -I https://yourdomain.hu          # 200, and a valid (non-self-signed) cert
curl -I https://yourdomain.hu/admin    # 200/302 from Filament
```
Open `https://yourdomain.hu` (SPA) and `https://yourdomain.hu/admin` in a browser.

### 🔒 Do this immediately — change the seeded admin
The seeder creates **`admin@evrst.test` / `password`**. On a public server, change
it right away:

1. Log into `/admin` with those credentials.
2. Open the user menu → your profile, **change the email to your real address and
   set a strong password.**

(Or from the shell: `docker compose exec backend php artisan tinker`, then update
the `User` record.)

Confirm `APP_DEBUG=false` and `APP_ENV=production` in `backend/.env.production`
(they are in the template).

---

## 8. Day-2 operations

**Deploy an update**
```sh
cd ~/evrst-demo && git pull && docker compose up -d --build
```

**Logs / status**
```sh
docker compose ps
docker compose logs -f backend          # web requests, migrations, errors
docker compose logs --since=1h web       # Caddy / TLS
```

**Run artisan / a shell in the app**
```sh
docker compose exec backend php artisan about
docker compose exec backend bash
```

**Restart one service**
```sh
docker compose restart queue
```

**Back up the data** (SQLite + uploads live in the `evrst_app-data` volume):
```sh
docker run --rm -v evrst_app-data:/data -v "$PWD":/backup alpine \
  tar czf /backup/app-data-$(date +%F).tar.gz -C /data .
```
Combine with Hetzner's server backups (enabled in step 1c) for off-box copies.

**Where things live**
- Code: `~/evrst-demo`
- DB + uploads: Docker volume `evrst_app-data` (mounted at `/persistent` inside the backend)
- TLS certs: Docker volume `evrst_caddy-data` (don't delete — avoids re-issuing)

---

## 9. Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| **Cert not issued / browser warning** | DNS not pointing at the box yet, or ports 80/443 blocked. `dig +short yourdomain.hu` must show `SERVER_IP`; check the firewall (step 1d). `docker compose logs web`. |
| **502 Bad Gateway** | Backend not healthy yet. `docker compose ps` (is `backend` healthy?) and `docker compose logs backend`. |
| **`FATAL: APP_KEY is empty`** | You didn't set `APP_KEY` in `backend/.env.production`. Generate with `echo "base64:$(openssl rand -base64 32)"`, then `docker compose up -d`. |
| **Build killed / out of memory** | Add the swap from step 3b. Or build elsewhere and `docker compose pull` instead. |
| **Admin emails never arrive** | Set a real `MAIL_MAILER` + API key (step 5a). The `queue` container must be running (`docker compose ps`) — it sends them. |
| **`env file ... not found`** | You skipped step 5 — `backend/.env.production` must exist. |
| **Port 80 already in use** | Something else (Apache/nginx) is on the host. `sudo ss -tlnp | grep :80` and stop it. |
| **Changed the domain** | Edit `SITE_ADDRESS` in `.env` and `APP_URL` in `backend/.env.production`, then `docker compose up -d --build`. |

---

### Cost recap
CX22 (~€3.79) + IPv4 (~€0.50) ≈ **€4.30/mo net** (~€5.45 incl. 27% HU VAT), plus
your `.hu` domain (~€10/yr). TLS, DNS (Hetzner/Cloudflare), and Resend's free email
tier cost nothing.

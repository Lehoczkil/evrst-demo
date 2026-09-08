# Cloudflare R2 setup for EVRST

Step-by-step guide for wiring a free, S3-compatible bucket so user uploads (sponsor logos, drawings, Onshape GLBs, event banners, bug-report screenshots) live outside the container filesystem instead of inside it.

---

## 1. Why R2

Cloudflare R2 is the recommended choice because the free tier gives you 10 GB of storage, 1 M Class A operations, and 10 M Class B operations per month, which comfortably covers a club site. R2 charges **zero egress fees**, which matters because every admin page render pulls images from the bucket — a B2 or AWS bucket would meter every view. R2 speaks the S3 API, so nothing in `config/filesystems.php` or the Filament resources changes between providers; you only swap env vars.

**Alternatives.** Backblaze B2 also offers 10 GB free but caps free egress at 3× your stored bytes per day, which gets uncomfortable as the team grows. AWS S3 is pay-as-you-go with no real free tier and requires a credit card on file. Both work with the same env vars described below if you ever migrate.

---

## 2. Cloudflare account and bucket

1. Sign up at https://dash.cloudflare.com. The free Cloudflare account is enough; R2 sits under the same login.
2. When you first open R2 from the sidebar, Cloudflare may ask for a payment method to activate the product. Adding a card does **not** start charges as long as you stay inside the free tier (10 GB / 1 M Class A / 10 M Class B per month). Anything over those limits is billed; nothing else is.
3. Sidebar → **R2** → **Create bucket**.
   - **Name**: `evrst-uploads` (or anything you prefer; used as `AWS_BUCKET` later).
   - **Location**: `Automatic` is recommended — Cloudflare picks the closest region.
4. After creation, open the bucket → **Settings** tab. Note the **S3 API** endpoint, which has the shape:
   ```
   https://<account-id>.r2.cloudflarestorage.com
   ```
   Copy the **Account ID** (also visible at the bottom-right of the R2 dashboard) — you'll need it for `AWS_ENDPOINT`.

---

## 3. Create an R2 API token

R2 has its **own** token UI. Do not use Cloudflare → My Profile → API Tokens; those are different.

1. R2 (left sidebar) → **Manage R2 API Tokens** → **Create API Token**.
2. **Token name**: `evrst-uploads`.
3. **Permissions**: `Object Read & Write`.
4. **Specify bucket**: pick `evrst-uploads` only (least-privilege; the token cannot touch other buckets).
5. **TTL**: leave `Forever`, or set 1 year and rotate annually.
6. Click **Create**. Cloudflare shows the credentials **once**:
   - `Access Key ID`
   - `Secret Access Key`

   Copy both immediately into your password manager. If you lose the secret you must roll the token.

---

## 4. Enable public access for served images

The admin renders bucket files via direct `<img src>` URLs, so the bucket needs a public read URL.

### Easy path: r2.dev subdomain

1. Bucket → **Settings** → **Public access**.
2. Under the `r2.dev` subdomain entry, click **Allow Access**.
3. Cloudflare gives you a public URL like:
   ```
   https://pub-<hash>.r2.dev
   ```
   Copy that — it becomes `AWS_URL`. Drop the trailing slash.

### Better path: custom domain

If your domain is already on Cloudflare DNS, you can serve uploads from `cdn.evrst.demo` instead of `pub-*.r2.dev`:

1. Bucket → **Settings** → **Custom Domains** → **Connect Domain**.
2. Enter the subdomain (e.g. `cdn.evrst.demo`). Cloudflare creates the DNS record automatically if the zone is on Cloudflare.
3. Wait for the certificate to provision (a few minutes).
4. Use `https://cdn.evrst.demo` as `AWS_URL` instead.

Trade-off: custom domains take a few minutes longer to set up and require the zone to live on Cloudflare DNS, but uploads then load from your own domain rather than `pub-*.r2.dev`, which is nicer for branding and not subject to the `r2.dev` rate limit (which is fine for low-traffic sites but exists).

---

## 5. CORS rules

Skip this section. The EVRST stack uploads through Laravel — the SPA and Filament admin POST files to the backend, which then writes to R2 server-side. The browser never talks to the bucket directly, so no CORS preflight is involved. Only revisit CORS if you later add direct browser-to-R2 uploads (e.g. presigned PUT for very large files).

---

## 6. Wire the credentials into the backend env

1. Open `backend/.env.production` on the deploy host (the file `compose.yaml` loads into the `backend`, `queue` and `scheduler` services). On another host, set the same keys wherever that host injects environment variables.
2. Add or update the following keys. `config/filesystems.php` holds the defaults each one overrides:

   | Key | Value |
   | --- | --- |
   | `FILESYSTEM_PUBLIC_DRIVER` | `s3` (defaults to `local`) |
   | `FILESYSTEM_DISK` | `local` (leave alone — only the `public` disk goes to R2) |
   | `AWS_ACCESS_KEY_ID` | from step 3 |
   | `AWS_SECRET_ACCESS_KEY` | from step 3 |
   | `AWS_BUCKET` | `evrst-uploads` |
   | `AWS_DEFAULT_REGION` | `auto` |
   | `AWS_ENDPOINT` | `https://<account-id>.r2.cloudflarestorage.com` |
   | `AWS_URL` | public URL from step 4, no trailing slash |
   | `AWS_USE_PATH_STYLE_ENDPOINT` | `true` |

3. Recreate the containers so they pick up the new env: `docker compose up -d`. The queue worker and scheduler read the same file, so all three need the restart.

---

## 7. Verify it works

1. Once the containers are back up, open `/admin` and sign in.
2. Upload something that hits the public disk:
   - Sponsors → create a sponsor with a logo, **or**
   - Drawings → open the drawing studio and save a sketch.
3. Cloudflare dashboard → R2 → `evrst-uploads` → **Objects** tab. The new file should appear under `sponsors/<filename>` or `drawings/<filename>`.
4. Reload the admin page. The image should render from the bucket (right-click → Inspect → confirm the `src` is your `AWS_URL`).
5. Rebuild from scratch (`docker compose up -d --build`) and reload. The same image should still load, because the bytes now live in the bucket rather than in the container image — a fresh image no longer has to carry them.

---

## 8. Local development

No R2 account is required to develop locally. The repo's `config/filesystems.php` defaults `FILESYSTEM_PUBLIC_DRIVER` to `local`, so local uploads continue to land in `backend/storage/app/public/` and are served through `php artisan storage:link`. Leave the `AWS_*` keys unset in `backend/.env` (or set them only if you want to test the R2 wiring locally). The seeded admin and `bun run dev` flow works identically with or without R2.

---

## 9. Cost notes

The free tier is **10 GB stored**, **1 M Class A operations** (writes / list), and **10 M Class B operations** (reads) per month, with **no egress charges**. To exhaust the read budget you'd need ~330 K image reads per day, or roughly 50 K admin page views per day if each page renders ~6 images — well past anything a student team site will hit. To exhaust storage you'd need ~10 000 high-res sponsor/event images. Monitor usage at Cloudflare dashboard → R2 → **Overview** (per-bucket metrics) or → **Usage** (account-wide). Cloudflare emails when you cross 80 % of any limit.

---

## 10. Rotating or revoking the token

If the production env leaks (or a contributor walks away with the secret), go to R2 → **Manage R2 API Tokens**, find the token from step 3, and click **Roll** (rotate) or **Revoke** (kill it). Then update `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` in `backend/.env.production` and `docker compose up -d` so the containers pick up the fresh credentials.

# Neil and Avi's Coffee Shop ☕

A deliberately vulnerable PHP web app for a SecOps / DevSecOps demo.
Order virtual coffee, then watch it get hacked, detected, and auto-remediated.

> ⚠️ **This app is insecure on purpose.** Run it only on a VM whose NSG allows
> inbound 22/80 from your own IP. Never leave it open to the internet.

## Stack

| Service | Image | Role |
|---|---|---|
| `web` | nginx 1.27 | Serves the app on port 80, writes access logs to `logs/nginx/` |
| `php` | php 8.3-fpm (+mysqli) | Runs the app in `app/` |
| `db`  | mysql 8.4 | Seeded from `db/init.sql` on first start |

## Run it on the Azure VM

```bash
# one-time: install Docker on Ubuntu
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER && newgrp docker

# clone your repo, then from the repo root:
chmod +x reset.sh
./reset.sh
```

Browse to `http://<vm-public-ip>/`.

`./reset.sh` wipes the database volume and rebuilds everything from the seed.
Use it between rehearsals.

## Pages

| Path | What it is | Safe? |
|---|---|---|
| `/` | Homepage. Headline, tagline, announcement and theme come from the `settings` table | ✅ |
| `/menu.php?q=` | Drink search | ❌ **SQL injection** |
| `/order.php` | Place a virtual coffee order | ✅ prepared statements |
| `/admin/login.php` | Staff login (admins only) | ✅ query, ❌ unsalted MD5 hashes |
| `/admin/` | Back office: edit storefront text and theme, view orders, restore defaults | ✅ |

## Intentional weaknesses (the attack chain)

1. **SQLi in `app/menu.php`.** The `q` parameter is concatenated into the query.
   sqlmap finds boolean-blind, error-based, time-based and UNION techniques.
   This is the one line the pipeline / AI fix should change (to a prepared statement).
2. **Verbose DB errors.** The menu page prints the raw MySQL error.
3. **Unsalted MD5 password hashes** in `users`.
   - `neil` / `cappuccino` (admin) is in sqlmap's default wordlist and cracks on stage.
   - `avi` (admin) uses a strong password and does not crack. Good talking point:
     the weak hash only fell because the password was weak too.
   - Two baristas also crack, but baristas can't log in to the back office.
4. **Admin can restyle the homepage.** The "Emergency notice (red)" theme plus a new
   headline is the defacement. No file-write tricks needed, so it's reliable on stage.

Everything else uses prepared statements, so the fix stays scoped to one file.

## Verify Phase 2 (from your attacker machine)

```bash
sqlmap -u "http://<vm-ip>/menu.php?q=latte" -p q --batch -D coffeeshop -T users --dump
```

Expected: `cracked password 'cappuccino' for user 'neil'`. Then log in at
`/admin/login.php` as `neil`, set the theme to **Emergency notice**, change the
headline, and check the homepage. Hit **Restore defaults** (or `./reset.sh`) to undo.

## Layout

```
app/              PHP app (mounted read-only into php + web)
  includes/       config, header, footer (denied by nginx)
  admin/          back office
  assets/         stylesheet (no external fonts/CDNs: works offline)
db/init.sql       schema + seed data
nginx/            nginx site config
php/              php-fpm Dockerfile + ini
reset.sh          wipe + rebuild
```

## Next phases

- **Phase 3:** attack script (sqlmap → parse creds → admin login → deface).
- **Phase 4:** swap `web` for an nginx + ModSecurity/OWASP CRS image in detect-only
  mode. Logs already land in `logs/nginx/`.

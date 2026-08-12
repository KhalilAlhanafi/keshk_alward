# كشك الورد — Production Deployment Runbook
# Phase 13 — Deployment Checklist

> This document is the authoritative guide for every deployment from the first production launch onward.
> Follow every step **in order**. Skipping steps will cause undefined behavior.

---

## Pre-Requisites (Server Setup)

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.2+ | Extensions: pdo_pgsql, redis, bcmath, gd, intl, mbstring, xml, zip |
| PostgreSQL | 15+ | Separate DB per environment |
| Redis | 7+ | AOF persistence enabled |
| Supervisor | Latest | For queue workers |
| Nginx | 1.24+ | HTTPS termination, `root = public/` |
| Certbot / SSL | — | HTTPS **mandatory** before go-live |
| Node.js | 18+ | Only needed during deploy for `npm run build` |

---

## 1. Clone & Composer Install

```bash
git clone https://github.com/YOUR_ORG/kashk-al-ward.git /srv/kashk-al-ward
cd /srv/kashk-al-ward

# Install production deps (no dev packages in production)
composer install --no-dev --optimize-autoloader
```

---

## 2. Environment Configuration

Copy the production template and fill in real values:

```bash
cp .env.production .env
php artisan key:generate
```

**Critical `.env.production` values to confirm:**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://kashkalward.com`
- `DB_*` → real PostgreSQL credentials
- `REDIS_HOST` / `REDIS_PASSWORD` → real Redis credentials
- `MAIL_*` → real SMTP (for order confirmations)
- `SHAM_CASH_CALLBACK_URL` → must be the HTTPS public URL

---

## 3. Database Migration

```bash
# Run migrations with --force to skip the production confirmation prompt
php artisan migrate --force

# Run production-only seeders (categories, delivery areas, admin user)
# ⚠️  DO NOT run OrderSeeder in production (demo data only)
php artisan db:seed --class=AdminUserSeeder --force
php artisan db:seed --class=CategorySeeder --force
php artisan db:seed --class=AddonSeeder --force
php artisan db:seed --class=DeliveryAreaSeeder --force
```

---

## 4. Frontend Asset Build

```bash
npm ci
npm run build
```

This compiles and version-stamps all Vite assets into `public/build/`.
**Run this before reloading Nginx** to avoid broken asset URLs.

---

## 5. File Permissions & Storage Link

```bash
# Storage must be writable by the web server user (www-data / nginx)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create the public symlink for user-uploaded product images
php artisan storage:link
```

---

## 6. Laravel Production Optimizations

```bash
# Config, route, event, and view caching (speeds up every request)
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache

# Verify all caches are valid
php artisan optimize
```

---

## 7. Supervisor — Queue Worker

Create `/etc/supervisor/conf.d/kashk-queue.conf`:

```ini
[program:kashk-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /srv/kashk-al-ward/artisan queue:work redis --queue=notifications,default --sleep=3 --tries=3 --max-time=3600 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/kashk-queue.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start kashk-queue:*
```

**Queue priority:** `notifications` queue is processed before `default` so admin bell
notifications and customer order confirmations are never delayed by bulk jobs.

---

## 8. Laravel Scheduler — Cron

Add to the `www-data` crontab (`crontab -u www-data -e`):

```cron
* * * * * cd /srv/kashk-al-ward && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler handles:
- **Daily at 02:00 Damascus time** — Purge guest carts older than 30 days.
- **Daily at 07:00 Damascus time** — Send pending orders digest email to admin.

---

## 9. Nginx Configuration

```nginx
server {
    listen 80;
    server_name kashkalward.com www.kashkalward.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name kashkalward.com www.kashkalward.com;

    ssl_certificate     /etc/letsencrypt/live/kashkalward.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/kashkalward.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    root /srv/kashk-al-ward/public;
    index index.php;

    charset utf-8;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # Static assets — long cache, Vite fingerprinted filenames
    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 10. Redis Persistence

Edit `/etc/redis/redis.conf`:

```
# Append-only file (AOF) — survives server restarts
appendonly yes
appendfsync everysec

# Also keep RDB snapshots as a backup
save 900 1
save 300 10
save 60 10000
```

```bash
systemctl restart redis
```

> **Important:** The app uses separate Redis databases for different data:
> - `DB 0` → Queue & Sessions
> - `DB 1` → Cache
>
> Ensure Redis `databases` config is ≥ 2.

---

## 11. PostgreSQL Backups

Add to root crontab (`crontab -e`):

```cron
0 3 * * * pg_dump -U postgres kashk_al_ward | gzip > /backups/kashk-$(date +\%Y-\%m-\%d).sql.gz
# Keep last 30 days
0 4 * * * find /backups/ -name "kashk-*.sql.gz" -mtime +30 -delete
```

---

## 12. Health Check

Laravel exposes a built-in health endpoint configured in `bootstrap/app.php`:

```bash
curl -s https://kashkalward.com/up
# Expected: "OK" (200)
```

Use this URL in your load balancer or uptime monitoring (e.g., UptimeRobot).

---

## 13. Post-Deploy Verification Checklist

Run these manual checks after every deploy:

- [ ] `https://kashkalward.com` loads homepage correctly
- [ ] Admin panel accessible at `https://kashkalward.com/admin/dashboard`
- [ ] Place a test order via Cash on Delivery → order appears in admin panel
- [ ] Place a test order via Sham Cash → wallet code displayed correctly  
- [ ] Admin notification bell shows the new order badge
- [ ] Customer confirmation email dispatched (check mail logs)
- [ ] `php artisan queue:work` processes jobs without errors
- [ ] Redis cache hit on catalog page (check `php artisan tinker` → `Cache::has(...)`)
- [ ] `php artisan schedule:list` shows all scheduled tasks correctly

---

## 14. Rollback Procedure

If something goes wrong after deploy:

```bash
# 1. Put the app in maintenance mode immediately
php artisan down --message="جاري تحديث المتجر، سنعود قريباً" --retry=60

# 2. Revert to previous Git tag
git checkout tags/v1.x.x

# 3. Restore composer deps
composer install --no-dev --optimize-autoloader

# 4. If migrations are involved, restore from PostgreSQL backup
pg_restore -U postgres -d kashk_al_ward /backups/kashk-YYYY-MM-DD.sql.gz

# 5. Re-cache config/routes
php artisan optimize

# 6. Bring site back up
php artisan up
```

---

## Environment File Template (.env.production)

```dotenv
APP_NAME="كشك الورد"
APP_ENV=production
APP_KEY=          # Run: php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://kashkalward.com

APP_LOCALE=ar
APP_FALLBACK_LOCALE=ar
APP_FAKER_LOCALE=ar_SY
APP_TIMEZONE=Asia/Damascus

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
LOG_DEPRECATIONS_CHANNEL=null

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kashk_al_ward
DB_USERNAME=kashk_user
DB_PASSWORD=STRONG_PASSWORD_HERE

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis

CACHE_STORE=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=REDIS_PASSWORD_HERE
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@kashkalward.com
MAIL_PASSWORD=MAIL_PASSWORD_HERE
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@kashkalward.com"
MAIL_FROM_NAME="${APP_NAME}"
```

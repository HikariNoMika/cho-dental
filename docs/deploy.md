# Deploying on a VPS (Docker)

The application ships with a `Dockerfile` and `docker-compose.yml`. It is designed
to run **alongside** an existing Docker workload that already occupies port 8080:
this app is exposed on **port 8081** by default (`APP_PORT`).

## 1. Prerequisites on the VPS

- Docker + Docker Compose v2
- Git
- (Optional) Nginx on the host for HTTPS / a custom domain

## 2. Clone and configure

```bash
git clone https://github.com/HikariNoMika/cho-dental.git
cd cho-dental

# Create the production .env
cp .env.example .env

# Generate an application key
docker run --rm -v "$PWD:/app" -w /app php:8.3-cli php artisan key:generate --show
# paste the resulting base64:... value into .env as APP_KEY

# Recommended production values in .env:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://your-domain.example
# DB_CONNECTION=sqlite
# DB_DATABASE=/var/www/html/database/database.sqlite
```

## 3. Build and start

```bash
docker compose up -d --build
docker compose logs -f app
```

The container runs migrations automatically on startup and caches config/routes/views.
The app is reachable at `http://<vps-ip>:8081`.

## 4. Using port 80/443 instead (optional reverse proxy)

If you want HTTPS on the standard ports, put Nginx in front. Example
`/etc/nginx/sites-enabled/cho-dental`:

```nginx
server {
    listen 80;
    server_name your-domain.example;
    location / {
        proxy_pass http://127.0.0.1:8081;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Then obtain a certificate with `certbot --nginx -d your-domain.example`.

## 5. SQLite as the database

**Yes, SQLite works** and is the default for this deployment. Notes:

- The database file lives in the `cho_dental_data` Docker volume, so it survives
  container rebuilds. Back it up with `docker cp cho-dental:/var/www/html/database/database.sqlite ./backup.sqlite`
  or by snapshotting the volume.
- SQLite locks the whole database on write. For a single clinic with a handful of
  concurrent users this is fine. If you later need multi-user concurrency, switch to
  MySQL/Postgres by changing `DB_CONNECTION`/`DB_*` in `.env` and adding a database
  service to `docker-compose.yml`.
- Generated claim forms (DOCX/PDF) are stored under `storage/app/prints` in the
  `cho_dental_storage` volume.

## 6. Updating

```bash
git pull
docker compose up -d --build
```

Migrations run automatically on startup.

## 7. Seeded administrator

The admin account comes from `config/claims.php` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`).
Set those in `.env` before the first boot, or change them in the Users page later.

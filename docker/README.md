# Docker — Pinoox framework

Container setup for **Pinoox core and framework infrastructure** only.

## What is included

| Component | Role |
|-----------|------|
| **`pinoox:dev` image** | PHP 8.2, Apache, extensions, Node.js, Composer — no application bundle |
| **`pincore/`** | Framework core (mounted from the repository) |
| **System apps** | `com_pinoox_installer`, `com_pinoox_manager`, `com_pinoox_welcome` |
| **`mysql`** | Database for development |

## What is not in the image

- Apps installed later from the **Pinoox market** — stored in the `pinoox_apps` volume under `apps/`
- Application `vendor/`, `pinker/`, `uploads/`, `downloads/` — persistent Compose volumes

## Requirements

- [Docker Engine](https://docs.docker.com/get-docker/) with [Compose](https://docs.docker.com/compose/) v2
- The project directory must be readable by the Docker daemon (bind mounts). If you see a “path is not shared” or “mounts denied” error, allow that path in your Docker file-sharing settings or clone the repository to a location your setup already permits. See your platform’s Docker documentation for details.

## Quick start

```bash
cp .env.example .env
docker compose build
docker compose run --rm pinoox composer install
docker compose up -d
```

Open **http://localhost:8080** (or `APP_PORT` from `.env`).

### Web installer — database

| Field    | Value                |
|----------|----------------------|
| Host     | `mysql`              |
| Database | `pinoox` (see `.env`) |
| Username | `pinoox`             |
| Password | `pinoox`             |

Use the Docker service name **`mysql`**, not `localhost`.

### Writable directories

```bash
docker compose exec pinoox mkdir -p pinker uploads downloads
docker compose exec pinoox chown -R www-data:www-data pinker uploads downloads apps
```

## Commands

```bash
docker compose exec pinoox bash
docker compose exec pinoox php pinoox
docker compose exec pinoox composer install
```

### Theme builds (system apps)

```bash
docker compose exec pinoox bash -c "cd apps/com_pinoox_manager/theme/spark && npm ci && npm run build"
docker compose exec pinoox bash -c "cd apps/com_pinoox_installer/theme/magic && npm ci && npm run build"
docker compose exec pinoox bash -c "cd apps/com_pinoox_welcome/theme/welcome && npm ci && npm run build"
```

### After installing an app from the market

If the app ships a `composer.json`, install dependencies inside that app:

```bash
docker compose exec pinoox bash -c "cd apps/PACKAGE_NAME && composer install --no-dev"
```

## Host MySQL

| Setting  | Default     |
|----------|-------------|
| Host     | `127.0.0.1` |
| Port     | `3307`      |
| Database | `pinoox`    |
| User     | `pinoox`    |
| Password | `pinoox`    |

## Layout

```
docker-compose.yml
docker/pinoox/Dockerfile    # infrastructure image only
docker/pinoox/apache.conf
.env.example
```

**Volumes:** `pinoox_mysql_data`, `pinoox_vendor`, `pinoox_pinker`, `pinoox_apps`, `pinoox_uploads`, `pinoox_downloads`

**Containers:** `pinoox-app`, `pinoox-mysql`

## Troubleshooting

- **Composer** — run `docker compose run --rm pinoox composer install` before first `up` if `vendor/` is missing.
- **Bind mount denied** — adjust Docker file-sharing for your environment, or clone the repo to a permitted directory.
- **Database connection** — installer host must be `mysql`.
- **Permissions** — `docker compose exec pinoox chown -R www-data:www-data pinker uploads downloads apps`

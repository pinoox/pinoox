[![Pinoox banner](./.github/banner.jpg)](https://pinoox.com)

# Pinoox

**Build web apps in PHP — one project, many apps, zero clutter.**

Pinoox is an HMVC platform for PHP 8.2+. You install it once, then add **apps** under `apps/` — each app is a small MVC module with its own routes, database, and theme. Perfect when you want structure without carrying a heavy framework everywhere.

**[Read the documentation →](https://github.com/pinoox/docs)**

---

## Why Pinoox?

| You get | What it means for you |
|--------|------------------------|
| **App-first HMVC** | Split work into `com_your_shop`, `com_your_blog`, etc. — not one giant codebase |
| **Twig + optional SPA** | Server-rendered pages for SEO, or Vue / React / Vite panels when you need rich UI |
| **Built-in CLI** | `php pinoox app:create`, migrations, routes, frontend build — from the project root |
| **JSON API out of the box** | REST-style endpoints with a consistent response shape |
| **Composer core** | Framework lives in `vendor/pinoox/pincore` — your code stays in `apps/` |

### Docker (optional)

Docker packages **Pinoox core and framework infrastructure** (PHP, Apache, MySQL, Node, Composer) plus the three system apps (installer, manager, welcome). Third-party and market-installed apps are not part of the image. See [docker/README.md](docker/README.md).

```bash
cp .env.example .env
docker compose build
docker compose run --rm pinoox composer install
docker compose up -d
```

=======
Use it for company sites, contact forms, internal tools, admin panels, small APIs, and multi-section portals — all in one installation.

---

## Quick start

```bash
git clone https://github.com/pinoox/pinoox.git
cd pinoox
composer install
```

Point your web server at the project root (or use your usual MAMP / Apache / nginx setup), then open the site in the browser. The installer app guides first-time setup.

### Pinoox DevDB

For local app development, Pinoox can run without MySQL, PostgreSQL, or SQLite:

```bash
APP_ENV=local
DB_CONNECTION=auto
pinx migrate
```

When no real database is available, local projects fall back to **Pinoox DevDB** and store migration-derived schema plus JSON data under `storage/devdb/`. Developers still write normal migrations and use normal models or `DB::app()->table(...)`.

Useful DevDB commands:

```bash
pinx migrate --devdb
pinx migrate --devdb --preview
pinx devdb:status
pinx devdb:inspect posts
pinx devdb:export storage/devdb-export.json
pinx devdb:seed
pinx devdb:clear --force
```

DevDB writes:

- `storage/devdb/schema.json`
- `storage/devdb/data/{table}.json`
- `storage/devdb/meta/migrations.json`
- `storage/devdb/meta/sequences.json`
- `storage/devdb/meta/indexes.json`
- `storage/devdb/devdb.sqlite` when the SQLite DevDB engine is available

DevDB is development-only. Production never silently falls back to DevDB; configure MySQL, PostgreSQL, or SQLite before deploying.

By default, DevDB uses an internal SQLite database automatically when `pdo_sqlite` is available. That gives local developers better SQL compatibility, including raw SQL and real SQLite transactions, without installing a separate database server. If SQLite is not available, or if you set `DEVDB_ENGINE=json`, DevDB falls back to the JSON engine.

The JSON engine supports common CRUD, pagination, simple model relations (`belongsTo`, `hasOne`, `hasMany`), seeders, factories, nested `where` / `orWhere` queries, simple `innerJoin` / `leftJoin`, common aggregates (`count`, `sum`, `avg`, `min`, `max`), simple `groupBy` / `having`, and lightweight JSON transaction snapshots. Lock hints such as `lockForUpdate()` are accepted as development no-ops. Raw SQL on the JSON engine still fails with a clear error suggesting SQLite, MySQL, or PostgreSQL.

**Next steps (documentation):**

- [Installation](https://github.com/pinoox/docs/blob/master/en/start/installing-pinoox.md)
- [Your first app](https://github.com/pinoox/docs/blob/master/en/start/your-first-app.md)
- [Practical walkthroughs](https://github.com/pinoox/docs/blob/master/README.md#practical-walkthroughs) — API, blog, gallery, Vue, React, and more
- [Example source code](https://github.com/pinoox/docs/tree/master/source) — copy-ready apps you can drop into `apps/`

Docs are available in **English** and **فارسی**: [pinoox/docs](https://github.com/pinoox/docs).

---

## How it fits together

```
your-project/
├── apps/              ← your apps (MVC modules)
├── vendor/pinoox/pincore/   ← framework (Composer)
├── pinoox               ← CLI
└── index.php            ← web entry
```

Each app has Controllers, Models, routes, migrations, and a theme. The platform picks the active app from the URL — you focus on the feature, not wiring.

---

## Contributing

Ideas, fixes, and docs improvements are welcome. See [Contributing](https://github.com/pinoox/docs/blob/master/en/introduction/contributions.md) in the documentation repo.

---

## License

Pinoox is open-source software released under the [MIT License](LICENSE).

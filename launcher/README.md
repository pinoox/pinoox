# Launcher (project root)

Pre-Composer boot layer for the Pinoox installation.

Runs **before** `vendor/autoload.php`:

- resolves `PINOOX_BASE_PATH` / `PINOOX_CORE_PATH`
- checks PHP version and required extensions
- loads Composer, then hands off to `pincore/`

Entry points:

- `index.php` → `launcher/bootstrap.php`
- `pinoox` CLI → same
- `php pinoox serve` router → `launcher/server.php`

This folder stays at **project root** — not inside `pincore/` — so requirements can be validated even when Composer or the framework path is misconfigured.

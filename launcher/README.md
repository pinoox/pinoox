# Launcher (project root)

Pre-Composer boot layer for the Pinoox installation.

Runs **before** `vendor/autoload.php`:

- resolves `PINOOX_BASE_PATH` / `PINOOX_CORE_PATH` (default: `vendor/pinoox/pincore`)
- checks PHP version and required extensions (root + core `composer.json`, then `requirements.defaults.php`)
- shows requirement errors using `launcher/lang/*` and `launcher/assets/*` (no core/vendor needed)
- resolves core test paths via `launcher/test-paths.php` → `vendor/pinoox/pincore`
- loads Composer, then hands off to the Pinoox core package

Entry points:

- `index.php` → `launcher/bootstrap.php`
- `pinoox` CLI → same
- `php pinoox serve` router → `launcher/server.php`

The core package is installed via Composer as `pinoox/pincore` (default path: `vendor/pinoox/pincore`).
This folder stays at **project root** — not inside the core package — so requirements can be validated even when Composer or the framework path is misconfigured.

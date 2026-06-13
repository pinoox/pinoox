# Launcher (platform layer)

Pre-Composer boot layer for the Pinoox installation.

Runs **before** `vendor/autoload.php`:

- resolves `PINOOX_BASE_PATH` / `PINOOX_CORE_PATH` (`pincore/` clone → `.pincore` / env → `vendor/pinoox/pincore`)
- checks PHP version and required extensions (root + core `composer.json`, then `requirements.defaults.php`)
- shows requirement errors using `platform/launcher/lang/*` and `platform/launcher/assets/*` (no core/vendor needed)
- resolves core test paths via `platform/launcher/test-paths.php` → `vendor/pinoox/pincore`
- loads Composer, then hands off to the Pinoox core package

Entry points:

- `index.php` → `platform/launcher/bootstrap.php`
- `pinoox` CLI → same
- `php pinoox serve` router → `platform/launcher/server.php`

The core package is installed via Composer as `pinoox/pincore` (default path: `vendor/pinoox/pincore`).
This folder lives under **`platform/`** at the project root — not inside the core package — so requirements can be validated even when Composer or the framework path is misconfigured.

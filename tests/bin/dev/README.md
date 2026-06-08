# Dev maintenance scripts

One-off CLI tools for local development — **not** part of runtime or CI by default.

Run from project root:

```bash
php tests/bin/dev/utf8-scan.php
php tests/bin/dev/utf8-check-files.php apps/com_pinoox_manager/lang/fa/widget/storage.lang.php
php tests/bin/dev/utf8-check-installer-lang.php
php tests/bin/dev/normalize-php-encoding.php --dry-run
```

| Script | Purpose |
|--------|---------|
| `utf8-scan.php` | List PHP files that are not valid UTF-8 (read-only) |
| `utf8-check-files.php` | Check specific files for UTF-8 + JSON round-trip |
| `utf8-check-installer-lang.php` | Validate installer `lang/fa/*.lang.php` encoding |
| `normalize-php-encoding.php` | Convert PHP sources to UTF-8 without BOM (use `--dry-run` first) |

Removed from root `scripts/`: spacing batch fixers (`fix-php-spacing`, etc.) — they were migration-only and can corrupt formatting if re-run.

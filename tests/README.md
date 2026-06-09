# Pinoox tests

Feature tests live under `tests/Feature/` grouped by **domain** (folder) and **scenario** (test file). Unit tests live in `tests/Unit/`.

## Quick start

```bash
# Platform tests (interactive package picker defaults to platform)
php pinoox test

# Platform + every app that has apps/{package}/tests/
php pinoox test all
php pinoox test --all

# By domain (phpunit testsuite)
php pinoox test platform --suite=Database
php pinoox test platform --suite=Server

# App tests
php pinoox test com_my_shop
php pinoox test com_my_shop --feature

# Filter / groups
php pinoox test platform --filter=AppRegistry
php pinoox test platform --exclude-group=non-isolated

# List suites
php pinoox test --list-suites

# Direct Pest (same as php pinoox test platform)
php vendor/bin/pest --configuration=phpunit.xml --testsuite=Routing
```

## Isolation

Most tests are **isolated** — they never touch production apps or project assets:

- Use `com_test_*` package names via `testPackage('suffix')` or `TestSandbox::packageName()`.
- Writable files go under `tests/Fixtures/sandbox/` via `testSandbox('path/to/file')`.
- `cleanupTestArtifacts()` (before/after each test) removes `com_test_*`, sandbox files, and test caches.
- Shared helpers live in `tests/Support/` (never define duplicate global functions in test files).

### Non-isolated tests

Some tests depend on **local project state** or external services. They are tagged:

| Group | Meaning |
|-------|---------|
| `@group non-isolated` | May read `pinker/state`, need sqlite file, or run real CLI migrator |
| `@group project-state` | Expects pinker overrides present in the developer tree |

Run only isolated tests (CI-friendly):

```bash
php pinoox test platform --exclude-group=non-isolated
```

Installer DB integration (optional):

```bash
PINOOX_INSTALLER_INTEGRATION=1 php pinoox test platform --suite=Installer
```

### Helpers

| Helper | Purpose |
|--------|---------|
| `testSandbox('docroot/assets/x.js')` | Isolated file path |
| `testPackage('webfix')` | → `com_test_webfix` |
| `fakeApp($package, $files)` | Temporary app under `apps/` (cleaned automatically) |
| `cleanupTestArtifacts()` | Reset filesystem + registries |
| `writeTestApp()` / `deleteTestApp()` | Database feature helpers (`tests/Support/DatabaseTestHelpers.php`) |

## Dev maintenance scripts

Local-only CLI helpers live in `tests/bin/dev/` (not runtime, not CI). See [tests/bin/dev/README.md](bin/dev/README.md).

## Layout

```
tests/
├── Feature/
│   ├── Server/          # php pinoox serve, WebServerFix, ServeAppBinding
│   ├── Routing/         # AppRouter, routes, actions, URL matching
│   ├── Http/            # Request, response, API envelopes
│   ├── App/             # App boot, HMVC, registry, dependencies
│   ├── Cache/           # Route/action cache stores
│   ├── Config/          # Env, SystemConfig, pinker-sensitive config
│   ├── Database/        # DB connection, credentials sync
│   ├── Installer/       # Setup flow, migrations, provision
│   ├── Theme/           # Twig, Vite manifest, theme inheritance
│   ├── Debug/           # Exceptions, trace, portal context
│   ├── Kernel/          # HTTP kernel, boot
│   ├── Portal/          # Portal contracts (one file per portal)
│   ├── Integration/     # Cross-cutting transport / catalog scenarios
│   └── …
├── Unit/                # Pure unit tests (no HTTP boot)
├── Support/             # Shared helpers & sample classes
└── Fixtures/sandbox/    # Writable test workspace (gitignored contents)
```

## Adding a test

1. Pick the domain folder (or create one if it is a new area).
2. Name the file `{Feature}Test.php` for unit-style feature tests or `{Scenario}ScenarioTest.php` for flows.
3. Use `com_test_*` packages and `tests/Fixtures/sandbox/` for any disk writes.
4. Put reusable helpers in `tests/Support/` — do not copy helpers into multiple test files.
5. Tag tests that need local pinker/DB/CLI with `@group non-isolated`.

Scaffold via CLI:

```bash
php pinoox test:create ProductTest com_my_shop --feature
```

## php pinoox test options

| Option | Description |
|--------|-------------|
| `package` | `platform`, `all`, or app package (e.g. `com_my_shop`) |
| `--all` | Same as `php pinoox test all` |
| `--suite`, `-s` | Platform testsuite from `phpunit.xml` |
| `--unit`, `-u` | `tests/Unit` only |
| `--feature` | `tests/Feature` only |
| `--filter`, `-f` | Pest/PHPUnit name filter |
| `--group`, `-g` | Run `@group` |
| `--exclude-group` | Skip `@group` (e.g. `non-isolated`) |
| `--coverage`, `-c` | Code coverage report |
| `--list-suites` | Show platform suite names |

# Pinoox tests

Feature tests live under `tests/Feature/` grouped by **domain** (feature) and **scenario** (individual test files).

## Isolation

- **Never touch production apps** (`com_pinoox_*`) or project root assets in tests.
- Use `com_test_*` package names via `testPackage('suffix')` or `TestSandbox::packageName()`.
- Writable files go under `tests/Fixtures/sandbox/` via `testSandbox('path/to/file')`.
- `cleanupTestArtifacts()` (before/after each test) removes `com_test_*`, sandbox files, and test caches.

### Helpers

| Helper | Purpose |
|--------|---------|
| `testSandbox('docroot/assets/x.js')` | Isolated file path |
| `testPackage('webfix')` | → `com_test_webfix` |
| `fakeApp($package, $files)` | Temporary app under `apps/` (cleaned automatically) |
| `cleanupTestArtifacts()` | Reset filesystem + registries |

## Dev maintenance scripts

Local-only CLI helpers live in `tests/bin/dev/` (not runtime, not CI). See [tests/bin/dev/README.md](bin/dev/README.md).

## Run by domain

```bash
php vendor/bin/pest --testsuite=Server
php vendor/bin/pest --testsuite=Routing
php vendor/bin/pest tests/Feature/Server/WebServerFixTest.php
```

## Layout

```
tests/Feature/
├── Server/          # php pinoox serve, WebServerFix, ServeAppBinding
├── Routing/         # AppRouter, routes, actions, URL matching
├── Http/            # Request, response, API envelopes
├── App/             # App boot, HMVC, registry, dependencies
├── Cache/           # Route/action cache stores
├── Config/          # Env, SystemConfig, pinker-sensitive config
├── Database/        # DB connection, credentials sync
├── Installer/       # Setup flow, migrations, provision
├── Theme/           # Twig, Vite manifest, theme inheritance
├── Routing/         # AppRouter, routes, actions, RouteContextResolver
├── Debug/           # Exceptions, trace, portal context
├── Kernel/          # HTTP kernel, boot
├── Portal/          # Portal contracts (one file per portal)
├── Storage/         # Filesystem storage
├── Docs/            # PinDoc, API doc inference
├── Integration/     # Cross-cutting transport / catalog scenarios
└── Support/         # Shared sample classes for tests
```

## Adding a test

1. Pick the domain folder (or create one if it is a new area).
2. Name the file `{Feature}Test.php` for unit-style feature tests or `{Scenario}ScenarioTest.php` for flows.
3. Use `com_test_*` packages and `tests/Fixtures/sandbox/` for any disk writes.

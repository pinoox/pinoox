# Pinoox App Cache

Runtime cache system for faster route, API, boot, Twig, GraphQL, and Pinker loading — scoped per app under `pinker/apps/{package}/cache/`.

---

## Build time vs install time (`.pinx`)

| Strategy | When | What to cache | Recommendation |
|----------|------|---------------|----------------|
| **Build time** | Creating `.pinx` package | routes, api, boot (serializable), twig, graphql | **Recommended** — ship pre-built cache inside the package |
| **Install time** | After app install on target server | pinker config bake, env-sensitive overrides | Run `cache:build` once after install if sources changed |
| **Runtime** | Each request | Load from cache if fresh; skip re-parsing | **Opt-in** — only when `cache.enabled => true` in `app.php` |

**Why build-time cache in the package?**

- Routes/API/boot/graphql are derived from source files — identical on every install.
- Twig compiled templates are CPU-heavy — baking them once saves cold-start time.
- The cache lives under `pinker/apps/{package}/cache/` and can be included in the distributable bundle.

**Why not only build-time?**

- Pinker **runtime overrides** (router.config, env-based config) must be baked on the target machine.
- After upgrading app source on a server, checksum detects stale cache → rebuild with `cache:build`.

**Conclusion:** Pre-build cache into `.pinx` **and** run `cache:build --only=pinker` after install for environment-specific Pinker files.

---

## Cache location

```
pinker/
└── apps/
    └── com_acme_shop/
        ├── app.php              ← Pinker baked config
        └── cache/
            ├── manifest.php     ← checksums + built_at
            ├── routes.php       ← action/route manifest
            ├── api.php          ← normalized API registry
            ├── boot.php         ← boot API/graphql contributions
            ├── graphql.php
            └── twig/            ← compiled Twig templates
```

Cache files use Pinker-style PHP (`<?php return [...];`) for faster `include` loading and to avoid casual direct reads in the browser.

Legacy `.json` files in the same folder are auto-migrated on read. The old global path `pinker/cache/actions/{package}.json` is also read once and moved to `routes.php` under the app cache folder.

---

## App config (`app.php`)

```php
'cache' => [
    'enabled' => false,       // runtime opt-in — default off everywhere
    'mode' => null,           // development | production | null = from ~pinoox.mode / runtime
    'stores' => [             // used when runtime cache is enabled
        'routes' => true,
        'api' => true,
        'boot' => true,
        'twig' => true,
        'graphql' => true,
        'pinker' => true,
    ],
    'twig' => [
        // extra Twig Environment options
    ],
    'build' => [
        'include_in_package' => true,  // hint for .pinx build pipeline
        // optional override for cache:build / install (ignores mode defaults):
        // 'stores' => ['routes' => true, 'pinker' => true],
    ],
],
```

### Runtime vs build

| Phase | Default | Controlled by |
|-------|---------|---------------|
| **Runtime** (each request) | Off | `'cache' => ['enabled' => true]` in `app.php` |
| **Build** (`cache:build`, `.pinx` install) | On (mode-based) | Project/app mode + optional `cache.build.stores` |

Runtime cache never turns on automatically in production — you must opt in per app.

### Modes (build/install)

| Mode | Runtime default | `cache:build` / install builds |
|------|-----------------|--------------------------------|
| `development` | off | `pinker` only |
| `production` / `staging` | off (until opt-in) | all stores listed in `cache.stores` |

Enable runtime cache explicitly:

```php
'cache' => ['enabled' => true],   // load from pinker/apps/{package}/cache/ at runtime
'cache' => ['enabled' => false],  // default — always parse sources on each request
```

---

## CLI

```bash
# Build all stores for all apps
php pinoox cache:build

# One app
php pinoox cache:build com_acme_shop

# Specific stores
php pinoox cache:build com_acme_shop --only=routes --only=api --only=twig

# Force rebuild
php pinoox cache:build com_acme_shop --force

# Clear
php pinoox cache:clear
php pinoox cache:clear com_acme_shop --only=twig
```

Pinker-only rebuild (existing command still works):

```bash
php pinoox pinker:rebuild com_acme_shop
```

---

## Portal & helper

```php
use Pinoox\Portal\AppCache;

AppCache::enabled();                    // bool for current app
AppCache::build('com_acme_shop');       // build all stores
AppCache::clear('com_acme_shop', ['twig']);

app_cache_build('com_acme_shop', ['api'], force: true);
```

---

## Stores

| Store | Builds | Runtime load |
|-------|--------|--------------|
| `routes` | Action/route manifest (metadata + controller refs) | `ActionRegistry` preload; Symfony still loads routes from PHP |
| `api` | PinDoc API registry | `AppApiRegistry` skips `require api.php` |
| `boot` | Serializable boot contributions | `AppBootstrap` hydrates without running `boot.php` |
| `twig` | Compiles theme templates | Twig native `cache` directory (same as Twig Environment) |
| `graphql` | GraphQL registry | `GraphQLRegistry` fast path |
| `pinker` | Baked app/config files | Pinker read path |

### Routes cache vs Symfony/Twig

- **Symfony Router** — route matching still comes from PHP route files at runtime. Pinoox does not serialize closures or Symfony `RouteCollection` objects; that remains Symfony's responsibility if you add a compiled router cache later.
- **Twig** — compiled templates live in `cache/twig/` and are loaded through Twig's own cache option (`AppCacheConfig::twigOptions()`).
- **`routes.php` manifest** — stores **metadata only**: action names, linked route names, flows/tags, and a serializable `handler_ref` (`Controller::method`, `@action`, invokable class). Closures are marked `cacheable: false` and are never written as executable handlers.

### Closure handlers (`fn () => ...`) — still work

Non-cacheable handlers are **not skipped**. They always run from the original PHP route files:

```
Request
  → Router::build()
  → include routes/actions.php   ← closure registered here (live)
  → Symfony matches route
  → closure / controller executes
```

The `routes` store **never replaces** route loading. Cache is only a manifest for CLI (`route:actions`), validation, and docs. After routes load, `ActionRegistry` keeps the live closure handler (merge mode) even when a cached manifest exists.

| Handler | Cached in manifest? | Executes at runtime? |
|---------|---------------------|--------------------|
| `[MainController::class, 'home']` | Yes (`handler_ref`) | Yes (from source + manifest) |
| `fn () => View::render('x')` | Metadata only (`cacheable: false`) | Yes (always from source) |
| Inline route closure `get('/', fn () => ...)` | Not in action manifest | Yes (from source via Symfony) |

**When to use which**

- **Closure** — quick prototypes, one-off callbacks, local/dev-only logic.
- **Controller method** — production apps, `.pinx` packages, anything you want in `route:actions --json`, PinDoc, or pre-built manifests.

Example — both work; only the controller ref is cacheable:

```php
// OK — runs every request from source; manifest marks cacheable: false
action('ping', fn () => response('pong'));

// Preferred for distributable apps — manifest stores handler_ref
action('home', [MainController::class, 'home']);
```

### Boot cache limits

Boot cache stores **serializable** data only (API manifests, GraphQL maps). Closures in `boot.php` web routes are **not** cached — those still run from source when needed.

---

## Freshness

Each store records a **checksum** of source files in `manifest.php`. Runtime cache is used only when:

1. `cache.enabled` is **explicitly** `true` for the app
2. Store file exists
3. Checksum matches current sources

`cache:build` and `.pinx` install always write cache files according to **build mode** (see `cache.build.stores`), even when runtime cache is off.

---

## `.pinx` build workflow (recommended)

```bash
# 1. Build distributable caches
php pinoox cache:build com_acme_shop --force

# 2. Package app + pinker/apps/com_acme_shop/cache/ into .pinx

# 3. After install on target
php pinoox cache:build com_acme_shop --only=pinker
```

---

## Integration with Pinker

- **Pinker** bakes `app.php` and config PHP → `pinker/apps/{package}/`
- **App cache** bakes runtime manifests → `pinker/apps/{package}/cache/`
- Both belong to the same app bundle and ship together in `.pinx`

Use `pinker:status` to inspect Pinker files; `cache/manifest.php` for runtime cache metadata.

---

## Disable for debugging

```php
// app.php
'cache' => ['enabled' => false],
```

Or set project mode in `~pinoox` config:

```php
'mode' => 'development',
```

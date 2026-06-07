# Pinoox App Router & Domain Routing

Pinoox decides **which app handles a request** in two layers:

1. **Domain routing** — `system/config/domain.config.php`
2. **Path routing** — `system/config/app/router.config.php`

Domain routing is evaluated first. If no host mapping matches, Pinoox falls back to path prefixes.

---

## Resolution order

```
HTTP request
  → match host in domain.config.php
  → else match longest path prefix in router.config.php
  → else use "/" default route
  → else use "*" wildcard route
  → else fallback to com_pinoox_welcome
```

Only **enabled** apps (`enable => true` in `app.php`) are selected.

---

## Path routing

File: `system/config/app/router.config.php`

```php
return [
    '/' => 'com_pinoox_installer',
    '/manager' => 'com_pinoox_manager',
    '/shop' => 'com_my_shop',
    '/shop/admin' => 'com_my_shop_admin',
];
```

Rules:

- Paths are normalized to `/segment` form.
- The **longest matching prefix** wins.
- `/shop/admin/users` matches `/shop/admin` before `/shop`.

### CLI

```bash
php pinoox app:router
php pinoox app:router -p com_my_shop
php pinoox app:router -u /shop
php pinoox app:router set /shop com_my_shop
php pinoox app:router remove /shop
```

---

## Domain routing

File: `system/config/domain.config.php`

### Default public domain

Used by docs/emails/CLI when no HTTP request exists:

```php
'default' => env('PINOOX_DOMAIN', 'https://example.com'),
```

### Host mapping

Simple form:

```php
'hosts' => [
    'shop.example.com' => 'com_my_shop',
    'admin.example.com' => 'com_my_admin',
],
```

Advanced form:

```php
'hosts' => [
    'panel.example.com' => [
        'package' => 'com_my_panel',
        'path' => '/',
    ],
],
```

Wildcard subdomains:

```php
'hosts' => [
    '*.example.com' => [
        'package' => 'com_tenant',
        'subdomain' => '{sub}',
    ],
],
```

When `tenant1.example.com` is requested:

- app package: `com_tenant`
- captured subdomain: `tenant1`

Legacy flat entries outside `hosts` are still supported:

```php
'api.example.com' => 'com_my_api',
```

### CLI

```bash
php pinoox app:domain
php pinoox app:domain --host tenant.example.com
```

---

## PHP API

```php
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\App\Domain;

// Current resolved app
App::package();
App::pathRoute();

// Router introspection
AppRouter::host();
AppRouter::subdomain();
AppRouter::routes();
AppRouter::getByPackage('com_my_shop');

// Domain config
Domain::match('shop.example.com');
Domain::hostMap();
Domain::defaultHost();
```

After resolution, the active layer exposes metadata:

```php
$layer = AppRouter::resolved();
$layer->matchedBy();   // domain | path | default | wildcard | fallback
$layer->host();        // request host when available
$layer->subdomain();   // captured wildcard subdomain
```

---

## Dedicated domain vs shared domain

| Setup | Request | Result |
|-------|---------|--------|
| Dedicated domain | `shop.test/` | app `com_my_shop`, base path `/` |
| Shared domain | `localhost/shop` | app `com_my_shop`, base path `/shop` |

For dedicated domains, internal app routes do **not** include the package folder in the URL. The app router receives the full path from `/`.

For shared domains, Pinoox strips the mapped prefix before the app router runs.

---

## Query route mode

When `index.php?_pnx=...` is used (`_pnx` = internal Pinoox path routing), package resolution follows the same domain-first and longest-prefix rules through `QueryRouteConfigLoader`.

---

## Testing

See `tests/Feature/AppRouterSystemTest.php` for:

- longest prefix matching
- domain before path precedence
- wildcard subdomain capture
- disabled app fallback

---

## See also

- [Pinoox Router & Named Actions](pinoox-router.md)
- HMVC structure — `.cursor/rules/pinoox-hmvc-structure.mdc`

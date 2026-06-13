# Welcome theme (`profile: hybrid`)

Public landing page for Pinoox — **Twig shell** (SEO, bootstrap) + **Vue** (interactive UI).

See [Frontend Standard](../../../docs/pinoox-frontend.md).

## Stack

| Item | Value |
|------|--------|
| Profile | `hybrid` |
| Stack | Vue 3 + Vite |
| Entry | `src/main.js` |
| Shell | `main.twig` |

## Development

```bash
# Terminal 1 — PHP
php pinoox serve --app=com_pinoox_welcome

# Terminal 2 — Vite HMR
php pinoox theme:frontend dev com_pinoox_welcome
```

`.env` (project root):

```env
VITE_DEV=true
VITE_DEV_SERVER=http://127.0.0.1:5173
VITE_SERVER_URL=http://127.0.0.1:8000
```

## Production build

```bash
php pinoox theme:frontend build com_pinoox_welcome
```

## Layout

```text
main.twig
├── partials/head.twig    → seo_tags()
├── partials/scripts.twig → pinoox_bootstrap(), vite_tags()
└── #app                  → Vue mounts here
```

SEO and `window.__PINOOX__` (URLs + page props) are set via `pinoox_bootstrap()` in `MainController::home()`.

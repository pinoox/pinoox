# Release Notes

## [Unreleased](https://github.com/pinoox/pinoox/compare/3.3.6...master)

## [v3.3.6 (2026-07-08)](https://github.com/pinoox/pinoox/releases/tag/3.3.6)

## Added

- Add **Pinx Inspector** for local development at `/~inspector` — database browser, routes, migrations, health, logs, and a floating in-app widget (requires `pinoox/pinx-inspector` as `require-dev`).
- Add **`php pinoox dev platform`** — one shared PHP serve (platform router) plus Vite HMR for every routed app with a frontend theme.
- Add **Pinoox Vite HMR** via `@pinooxhq/vite-plugin`: auto `pinooxDevState` / `pinooxServer` wiring, `.pinoox/dev.json`, and Twig `vite_tags()` refresh during dev.
- Add **DevDB** as a platform `require-dev` dependency with development defaults in `.env.example` (zero-setup local SQLite/JSON workflow).
- Add global **Pinx drag-and-drop** to open the package installer from the Manager desktop.
- Document the recommended Pinx single-app development workflow, from first app setup through build and release.
- Add local development guidance for Pinoox DevDB as the default zero-setup database workflow.
- Add clearer development setup notes for Composer-based Pincore and Pinx package workflows.

## Changed

- Improve **`php pinoox dev`** terminal output: defer links until Vite is ready, platform-style URL table, compact Inspector indicator, cleaner single-app serve-lock banner.
- Improve **Inspector platform mode**: app selector respects `?package=`, serve-lock hides multi-app picker when one app is bound, route list uses AppRouter.
- Improve **Inspector widget**: Shadow DOM style isolation (always on top, independent of app CSS), panel collapse, and full hide with restore tab.
- Improve **Manager**: cached users/themes tabs, safer theme delete, market panel shell aligned with shared window styles, DB prefix from route slug with conflict resolution.
- Refresh **Welcome** theme UI and development docs for the Vite HMR workflow.
- Improve console bootstrap behavior for UTF-8 output in Pinoox CLI contexts.
- Refine the single-app development experience around local environment defaults and development-only tooling.
- Prefer local `pincore/` classmap over `vendor` when booting the CLI in a linked workspace.

## Fixed

- Fix Inspector package/CLI resolution for `php pinoox dev {app}` (no more `unknown` package or missing `bin/pinx` on platform installs).
- Fix Inspector not reloading data when switching apps in platform multi-app mode.
- Fix `php pinoox dev platform` being parsed as a theme name instead of starting platform dev.
- Fix Persian/UTF-8 CLI output initialization for terminal commands.
- Fix Installer PHP minimum version check to read from root `composer.json`.
- Fix Market panel surface opacity in simple mode (`MarketPanelShell`).

## Notes

- Platform distribution **3.3.6** (`platform/pinoox.config.php`). Kernel ships via **`pinoox/pincore` ^3.4** (3.5.x recommended for latest dev/Inspector fixes).
- Optional dev tools: `pinoox/pinx-inspector` ^1.3, `pinoox/devdb` ^1.4.
- Inspector and the dev widget are **local-only**; disable with `--no-inspector` on `serve` / `dev`.

## [v3.1-beta (2026-06-09)](https://github.com/pinoox/pinoox/releases/tag/3.1-beta)

## Added

- Introduce the new Pinoox platform generation with a clear split between the platform project and Pincore 3.
- Move the framework engine into the Composer package `pinoox/pincore`.
- Add root-level platform configuration for apps, domains, app routing, and deployment settings.
- Add path aliases such as `~project` and `~pincore` for predictable project and core paths.
- Add guided setup behavior when Composer dependencies are missing.
- Add stronger developer tooling for frontend assets, dependency management, testing, app creation, version checks, and Pinker cache rebuilds.
- Add smarter multi-app routing with domain-first matching, longest path-prefix matching, wildcard subdomains, shared URL bases, and query-route mode.
- Add per-app baked runtime cache under `pinker/apps/{package}/cache/`.

## Changed

- Replace the embedded core layout with a Composer-managed Pincore layer.
- Separate platform versioning from kernel versioning.
- Modernize the Manager, Installer, and Welcome frontends for the new platform structure.
- Improve deployment and hosting clarity by keeping project files under `config/`, `apps/`, and `storage/`.

## Notes

- This is a beta release with breaking changes from Pinoox 2.1.0.
- Requires PHP 8.2+, Composer 2.x, and Node.js for Vite theme development/build workflows.

## [v2.1.0 (2026-02-24)](https://github.com/pinoox/pinoox/releases/tag/2.1.0)

## Added

- Upgrade Pinoox Manager to Vue 3 with Vite 6, Tailwind CSS 4, SCSS, and BEM-based frontend structure.

## Changed

- Migrate the Manager frontend from Vue 2 to Vue 3.
- Redesign the Manager interface with a cleaner, more minimal UI.
- Improve API-related components for better speed and maintainability.
- Improve app management user experience.

## Fixed

- Fix known issues in app management modules.

## [v2.0.1 (2025-05-29)](https://github.com/pinoox/pinoox/releases/tag/2.0.1)

## Changed

- Update installer compatibility checks to require PHP 8.2.0 or newer.
- Update the Pinoox Twig template with the latest compatibility and maintenance changes.

## [v2.0.0 (2025-05-28)](https://github.com/pinoox/pinoox/releases/tag/2.0.0)

## Added

- Introduce a rewritten Pinoox 2 foundation.
- Add a container manager for dependency lifecycle handling.
- Add dependency injection support.
- Add Composer-based package management.
- Add the Pinoox CLI for common development workflows.
- Add ORM support for database records.
- Add Portal facades for expressive access to framework services.
- Add database migrations.
- Add an event manager.
- Add Flow as a middleware-like request lifecycle system.

## Changed

- Improve service management flexibility.
- Modernize core architecture for a more developer-friendly framework foundation.

## [v1.6.8 (2021-11-16)](https://github.com/pinoox/pinoox/compare/1.6.8...master)  

## Added

- Add a quick connection to a Pinoox account
- Add beautiful dumb component
- Add template installation in the manual installer
- Add the ability to upload base64 files

## Changed

- Improved performance of the controllers under the folder
- Upgrade Uploader Component
- Improving HTTP Request component in curl method

## Fixed

- Fix a problem with installing Pinoox on version older MySQL
- Fix result validation generate
- Fix date problem in the validation component
- Fix an issue with receiving meta information in the templates list
- Fix an authorization recognition problem on some web servers
- Minor bug fixes
 
 
## [v1.6.0 (2020-12-28)](https://github.com/pinoox/pinoox/compare/1.6.0...master)  

 ## Added
 
 - Optimized with PHP 8
 
 ## Changed
 
 - Optimize template installation
 
 ## Fixed
 
 - Fixed move to the welcome page after installation
 - Fixed show active template after activation
 - Fixed implode problem in DB component
 - Fixed the problem of checking the Public method on the router
 
 
## [v1.5.8 (2020-12-20)](https://github.com/pinoox/pinoox/compare/1.5.8...master)   

## Added

- Load PHP file when running an application
- Get a list of templates in the template component
- Insert template information in the template component

## Changed

- Improved thumbnail creation in the upload component

## Fixed

- Problem recognizing the HTTPS protocol
- Fixed the problem of displaying the cover image of the templates


## [v1.5.5 (2020-09-13)](https://github.com/pinoox/pinoox/compare/1.5.5...master)   

## Added

- Add tabs and improved navigation in the manager
- Add app preview privately in the manager (secret view)
- Add template management 
- Add dashboard app
- Add notifications
- Add drag & drop for manual installation packages templates
- Add Pinoox Baker (pinker) for easier app management & development
- Add floating Installer 
- Add sidebar component in manager for managing menus
- Add download and install templates from market 

## Changed

- Improved the installation and update process
- Improved market UI/UX
- Upgrade File, Zip, Lang, and Upload components
- Improved downloader in the market
- Improved progress bar in manager

## Fixed

- Fixed bug headers HttpRequest component
- Fixed static text title and use Lang system instead
- Fixed alert messages text 
- Fixed routes in app manager
- Fixed bug for scrolling app details in the market


## [v1.4.6 (2020-05-19)](https://github.com/pinoox/pinoox/compare/1.4.6...master)   

## Added

- Add Karla font for English
- Add multiple parametric routes to routing

## Changed

- Optimize Pinoox installation file (reduce size)
- Ability to support emojis in database with UTF8MB4

## Fixed

- Fixed the user's session lifetime
- Fixed database problems in some systems with limited resources
- Fixed the installer problem in some devices to check the required resources
- Fixed the problem of displaying images on iOS
- Fixed some minor problems...

## [v1.4.0 (2019-12-28)](https://github.com/pinoox/pinoox/compare/1.4.0...master)   

## Added

- Connect pinoox account to market
- Add app management section
- Ability to change the configuration of application
- Ability to hide the app in the desktop dock
- Add a Live Wallpaper (animated)
- Add new wallpapers
- Ability to see users of an app

## Changed

- Changed the user authentication to JWT method and improved security
- Redesigned and improved the app market
- Increased market loading speed
- Optimized the application installation process

## [v1.2.1b3 (2019-08-30)](https://github.com/pinoox/pinoox/compare/1.2.1b3...master)   

## Added

- English language

## Changed

- Ability to preview apps without having to define the route
- Ability to update apps from the market
- Displays the loading on the login and lock screen
- Optimized the application installation process
- Improved user interface


## Fixed

- Fixed delay problem in first run and optimize cache process
- Fixed redirect problem after login
- Fixed market load apps list because of AJAX cross domain blocking 
- Fixed page refresh after each update
- Fixed timezone issue in php versions that do not have the default timezone
- Fixed web servers that have PUT, DELETE, PATCH methods locked

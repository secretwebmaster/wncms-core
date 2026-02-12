# Overview

WNCMS is a modular, Laravel-powered CMS designed for building multilingual, multi-site websites and APIs. It ships with a small, opinionated core and encourages extending functionality through packages, themes, and managers. This guide gives you a high-level map of the system and points you to the right place based on your role.

## Who should read this

| Role              | What you do                                                                    | Where to start                         |
| ----------------- | ------------------------------------------------------------------------------ | -------------------------------------- |
| Client            | Use the browser dashboard to publish posts, pages, and links                   | [User Guide](/user/overview)           |
| Website Developer | Build custom models, controllers, managers, views, and routes in a Laravel app | [Developer Guide](/developer/overview) |
| Package Developer | Publish reusable WNCMS packages via Composer                                   | [Package Guide](/package/overview)     |
| API User          | Manage content in WNCMS and consume it from another app (Next.js, Vue, etc.)   | [API Reference](/api/overview)         |

## Key features

- **Laravel 12 foundation** with familiar Eloquent, Blade, routing, queues, and caching.
- **Modular core** with clean extension points for models, controllers, managers, resources, and routes.
- **Multi-language and multi-site** support via traits and helpers designed for real-world i18n.
- **Themeable frontend** that loads templates from `resources/views/frontend/theme/{themeId}` with optional `ThemeServiceProvider`.
- **First-class API** controllers and resources for building headless or hybrid sites.
- **Package lifecycle** with registration hooks, auto-migrations on activation, menus, and translations.

## Architecture at a glance

- **Core**: provided by `secretwebmaster/wncms-core` and includes base classes like `BaseModel`, `BackendController`, `FrontendController`, `ApiController`, base managers, traits (tags, multisite, translations), resources, routes, and backend UI.
- **App-level customizations**: create local models/controllers/managers that extend core classes and override behavior where needed.
- **Packages**: installable Composer packages that register models, migrations, seeders, controllers, managers, menus, translations, and routes.
- **Themes**: frontend templates, options, and widgets living under `resources/views/frontend/theme/{themeId}` with a `system/config.php` and optional provider.
- **API**: consistent resource layer and endpoints for posts, links, tags, users, websites, and more.

## Concepts you will see often

- **Model Manager**: a service wrapping list/get queries, filters, tags, caching, and pagination in a uniform way.
- **Tag system**: attach semantic categorization to any model (`post_category`, `post_tag`, `link_category`, etc.).
- **Translations**: translatable attributes resolved per request locale with clean fallbacks.
- **Caching**: cache keys and tags standardized per manager to speed up high-traffic pages.
- **Routes**: split into `frontend`, `backend`, `api`, and `install` for clarity and testability.

## What you can build

- A blog or documentation site using the backend and a theme.
- A content API consumed by a separate SPA or mobile app.
- A commercial plugin distributed on Packagist with its own menus, screens, and database tables.
- A full multi-site setup with shared user base and localized content.

## Requirements and installation

Before installing, check the [Requirements](/getting-started/requirements). When ready, follow the [Installation](/getting-started/installation) guide to set up a fresh Laravel project with `wncms-core`, enable the backend, and log in.

## Conventions

- **Namespaces**: core lives under `Wncms\*`. Your app code may extend and override these.
- **Views**: backend views use the `wncms::backend.*` namespace. Frontend themes live under `resources/views/frontend/theme/{themeId}`.
- **Translations**: use `__('wncms::word.xxx')` in PHP and `@lang('wncms::word.xxx')` in Blade.
- **No manual migrations for packages**: packages run migrations/seeders during activation in the backend.

## Versioning and compatibility

- Targets **Laravel 12** and PHP versions supported by that release.
- Semantic versioning for `wncms-*` packages. Follow each package’s changelog for upgrade notes.
- Breaking changes are announced in release notes with clear migration steps.

## Next steps

- Explore the [User Guide](/user/overview) to learn the dashboard.
- Read the [Developer Guide](/developer/overview) to extend models, controllers, and managers.
- Build and publish a plugin via the [Package Guide](/package/overview).
- Integrate a frontend app using the [API Reference](/api/overview).

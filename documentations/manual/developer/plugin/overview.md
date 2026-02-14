# Plugin Development Overview

WNCMS supports project-level plugins under `public/plugins` without Composer package registration.
This section explains how to build a basic plugin with lifecycle, hooks, views, and translations.

## Recommended Structure

Use this structure:

```
public/plugins/{plugin_id}/
├── plugin.json
├── Plugin.php
├── classes/ (optional)
├── system/events.php
├── system/functions.php
├── routes/web.php
├── views/{backend|frontend|common}/...
└── lang/{en|zh_CN|zh_TW|ja}/word.php
```

## Plugin Lifecycle

A plugin can implement lifecycle hooks through a standardized `Plugin.php` class extending `Wncms\Plugins\AbstractPlugin`.

- Load extra plugin class files from root `Plugin.php`.
- Keep `system/events.php` for listeners and `system/functions.php` for helper functions only.

- `init()` registers runtime hooks/events.
- `activate()` runs activation logic (for example default settings).
- `deactivate()` runs when plugin is disabled.
- `delete()` runs when plugin is removed.

## Recommended Reading

- [Create a Basic Plugin](./create-a-basic-plugin.md)
- [Developer Event Overview](../event/overview.md)
- [Developer Command Overview](../command/overview.md)
- [Developer Locale Translation Files](../locale/translation-files.md)

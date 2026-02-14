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

## Activation Compatibility Checks

Before activation, WNCMS validates plugin dependency and version compatibility from `plugin.json`.

- Activation fails when a required dependency plugin is missing.
- Activation fails when a required dependency exists but is not active.
- Activation fails when a dependency version does not match the declared version constraint.

Supported `dependencies` formats in `plugin.json`:

```json
{
  "dependencies": ["plugin-a", "plugin-b"]
}
```

```json
{
  "dependencies": {
    "plugin-a": "^1.2",
    "plugin-b": ">=2.0 <3.0"
  }
}
```

```json
{
  "dependencies": [
    { "id": "plugin-a", "version": "^1.2" },
    { "id": "plugin-b", "version": "~2.3" }
  ]
}
```

Supported version constraint styles:

- Exact: `1.2.3`
- Comparators: `>=1.2`, `<=2.0`, `!=1.4.0`
- Ranges (space/comma separated): `>=1.2 <2.0`
- Caret: `^1.2`
- Tilde: `~1.4`

## Deactivation Safety Checks

Before deactivation, WNCMS checks whether the plugin is required by any other active plugins.

- If active dependent plugins are found, deactivation is blocked.
- Error message lists the dependent plugin ids and tells user to deactivate those plugins first.

## Backend Plugin Index Visibility

Backend plugin index includes a `Required Plugins` column.

- It shows dependency plugin ids.
- If version constraints are defined, it shows `plugin_id (constraint)`.

## Recommended Reading

- [Create a Basic Plugin](./create-a-basic-plugin.md)
- [Developer Event Overview](../event/overview.md)
- [Developer Command Overview](../command/overview.md)
- [Developer Locale Translation Files](../locale/translation-files.md)

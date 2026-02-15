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

## Plugin Upgrade Lifecycle

Installed version is stored in `plugins.version`.
Available package version is read from `public/plugins/{plugin_id}/plugin.json` `version`.

- If `plugin.json` version is newer than `plugins.version`, update is available.
- Plugin index keeps displaying database fields only.
- Editing `plugin.json` directly does not immediately overwrite plugin table display fields.
- Upgrade is executed explicitly (backend upgrade action), then table metadata is synchronized from manifest.
- Plugin rows shown in `Plugins Index` must come from `plugins` table records.
- Plugins found in `public/plugins` without matching `plugin_id` record are shown in a separate `Raw Plugins` table.
- After first activation creates a matching table record, plugin appears in the regular `Plugins Index` table.

### Upgrade definition (deterministic map only)

Define upgrades in plugin lifecycle class:

```php
public array $upgrades = [
    '1.2.0' => 'upgrade_1_2_0.php',
    '1.3.0' => 'upgrade_1_3_0.php',
];
```

Execution rules:

- Only `$upgrades` entries are executable.
- Keys are target versions.
- Entries run in ascending version order.
- Runner executes steps where `installed_version < target_version <= available_version`.
- If available version is newer but no chain reaches it, upgrade fails.
- On first failed step, process stops and installed version is unchanged.
- On success, `plugins.version` is updated to available version.

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
- Plugin index keeps diagnostics under `Remark` as a single detail button.
- Clicking it opens one modal grouping `Last Load Error`, `Source File`, and raw `Remark` in terminal-style block output.
- `Status` column is hidden in index; activation state is inferred from action buttons (`Activate` / `Deactivate`).
- `URL`, `Path`, and `Required Plugins` columns are only shown when `show_detail` is enabled.

Load failure remarks are stored in a structured format:

```text
[LOAD_ERROR] YYYY-MM-DD HH:MM:SS {error_message} | source_file={absolute_file_path}
```

## Recommended Reading

- [Create a Basic Plugin](./create-a-basic-plugin.md)
- [Developer Event Overview](../event/overview.md)
- [Developer Command Overview](../command/overview.md)
- [Developer Locale Translation Files](../locale/translation-files.md)

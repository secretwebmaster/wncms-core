---
name: wncms-adding-system-settings
description: Add host-project or plugin system settings in WNCMS without editing vendor files, using documented settings events and gss/uss helpers.
---

## Goal
Expose extra system settings from a WNCMS host project or plugin without modifying vendor `wncms-core` files.

## When To Use
- adding project-specific settings to backend Settings UI
- adding plugin settings that should be persisted through WNCMS settings flow
- reading or updating custom settings with `gss()` / `uss()`
- extending backend settings tabs without overriding vendor controllers or views

## Read First
- `documentations/manual/developer/event/settings.md`
- `documentations/manual/developer/helper/overview.md`
- `documentations/manual/developer/plugin/create-a-basic-plugin.md`

## Hard Rules
- Do not edit vendor `config/wncms-system-settings.php` from a host project.
- Add custom settings by extending backend settings tabs through `wncms.backend.settings.tabs.extend`.
- Keep submitted field names under `settings[{key}]` so the built-in settings update flow persists them.
- Read setting values with `gss('key', $fallback)` in runtime logic.
- Write setting values with `uss('key', $value)` only when you need programmatic updates outside the normal settings form flow.
- Use stable snake_case keys for project settings. For plugin settings, grouped keys such as `plugin-id:key` are valid when needed.
- If a setting needs labels, descriptions, or help text, store them in host-project or plugin language files rather than editing vendor translations.

## Recommended Pattern
1. Register a listener for `wncms.backend.settings.tabs.extend` from app or plugin code.
2. Add or extend a tab definition in `$availableSettings`.
3. Define controls whose `name` maps to a persisted settings key.
4. Consume the saved value with `gss()` in runtime logic.

Example listener shape:

```php
Event::listen('wncms.backend.settings.tabs.extend', function (&$availableSettings) {
    $availableSettings['project'] = [
        'tab_name' => 'project',
        'tab_label_key' => 'custom.project_settings',
        'tab_content' => [
            [
                'type' => 'switch',
                'name' => 'enable_project_feature',
                'text_key' => 'custom.enable_project_feature',
            ],
        ],
    ];
});
```

## Do Not Invent
- Do not assume undocumented controller overrides are required for normal custom settings persistence.
- Do not query the `settings` table directly in feature logic when `gss()` / `uss()` already cover the use case.
- Do not claim vendor config edits are the preferred host-project extension path.

---
name: wncms-plugin-basic-creation
description: Guide for creating a basic non-composer WNCMS plugin under public/plugins with lifecycle class, hooks, views, and translations.
---

## Goal
Create a practical, minimal WNCMS plugin scaffold that can be discovered, activated, and extended safely.

## Scope
Use this skill when:
- creating a new plugin under `public/plugins`
- preparing plugin lifecycle class and hook listeners
- adding plugin views/translations/settings tab injection

## Required Structure
Plugin root example:

```
public/plugins/{plugin_id}/
├── plugin.json
├── Plugin.php
├── classes/
│   └── CustomClass.php (optional)
├── routes/
│   └── web.php
├── system/
│   ├── events.php
│   └── functions.php
├── views/
│   ├── backend/
│   ├── frontend/
│   └── common/
└── lang/
    ├── en/word.php
    ├── zh_CN/word.php
    ├── zh_TW/word.php
    └── ja/word.php
```

## Lifecycle Class Rules
- Use `Plugin.php` as standardized entry class file.
- Set plugin lifecycle class explicitly in `plugin.json` via `class`, or return a plugin instance/class string from entry file.
- Add direct-access guard:

```php
if (!defined('WNCMS_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}
```

- Class should extend `Wncms\Plugins\AbstractPlugin`.
- Load plugin class dependencies in root `Plugin.php` (for example `require_once __DIR__ . '/classes/CustomClass.php';`).
- Implement lifecycle methods as needed:
  - `init()` for runtime hook/event registration.
  - `activate()` for default settings/one-time activation logic.
  - `deactivate()` for disabling behavior.
  - `delete()` for cleanup behavior.

## Class Loading Rules
- Keep `system/functions.php` focused on helper functions only.
- Keep `system/events.php` focused on event listeners only.
- Do not `require_once` class files from `system/functions.php`; load class files from root `Plugin.php`.
- For runtime plugin instance access, prefer `Wncms\Plugins\PluginLoader`.

## Hook Naming
- Prefer canonical names such as:
  - `wncms.backend.users.update.after`
  - `wncms.view.backend.users.edit.fields`
- When consuming existing hooks, prefer documented event names from the manual.

## Translation Rules
- All plugin UI text should be translatable.
- `plugin.json` fields `name`, `description`, and `author` may be either a string or a locale map object.
- Use plugin namespace from plugin id, for example:
  - `@lang('wncms-users-telegram-option::word.telegram_username')`
- Recommended translation keys synced in all default wncms locales:
  - `en`, `zh_CN`, `zh_TW`, `ja`

## Settings Injection
- Prefer extending settings by listening:
  - `wncms.backend.settings.tabs.extend`
- Use grouped setting keys:
  - `{plugin_id}:{key}`
- Example key:
  - `wncms-users-telegram-option:enable_telegram_id`

## Verification Checklist
- Plugin appears in backend plugin list.
- Plugin can be activated by backend and `php artisan wncms:activate-plugin {plugin_id}`.
- `PluginServiceProvider` loads routes/events/functions/translations.
- Hook output appears in target backend/frontend views.
- No hardcoded UI strings remain in plugin views.

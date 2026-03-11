---
name: wncms-helper-usage
description: Use documented WNCMS helper access patterns, grouped settings helpers, and plugin loader APIs instead of legacy helper calls.
---

## Goal
Use WNCMS helper and service access patterns that are documented as current, not deprecated.

## When To Use
- replacing legacy helpers
- reading or writing system settings
- loading plugin lifecycle instances
- choosing between global helper calls and `wncms()` methods

## Read First
- `documentations/manual/developer/helper/overview.md`

## Hard Rules
- Prefer `wncms()->...` service access patterns for new code.
- Use `gss()` to read settings and `uss()` to update settings.
- Grouped setting syntax uses `group:key`, for example `wncms-users-telegram-option:enable_telegram_id`.
- Use `Wncms\Plugins\PluginLoader` for runtime plugin instance loading.
- Removed helpers such as `wncms_tag_word(...)` and `wncms_get_model_name_from_table_name(...)` should not be reintroduced.

## Do Not Invent
- Do not claim undocumented helper wrappers or fallback behavior.
- If a legacy helper still appears in code, treat the docs as the migration target, not proof that the helper should be used for new work.

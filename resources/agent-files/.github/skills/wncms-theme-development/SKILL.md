---
name: wncms-theme-development
description: Build or extend WNCMS themes using the documented theme directory layout, config structure, menus, templates, and pagination rules.
---

## Goal
Create or update themes in the documented WNCMS structure so assets, views, menus, and template options work with core expectations.

## When To Use
- creating a new theme
- changing theme `config.php`
- rendering menus in theme blades
- adding theme page templates
- building pagination UI in theme views

## Read First
- `documentations/manual/developer/theme/theme-structure.md`
- `documentations/manual/developer/theme/config.md`
- `documentations/manual/developer/theme/menu.md`
- `documentations/manual/developer/theme/page-templates.md`
- `documentations/manual/developer/theme/pagination.md`

## Hard Rules
- Follow the documented theme structure with `assets/`, `views/`, `lang/`, `config.php`, `functions.php`, and `screenshot.png`.
- Guard theme PHP entry files with the documented `WNCMS_THEME_START` direct-access check.
- In `config.php`, keep documented sections such as `info`, `option_tabs`, and `default`.
- Theme template config keys under `templates` must match actual blade files under `views/pages/templates/{blade_name}.blade.php`.
- Menu links for the homepage should use `frontend.pages.home`.
- Pagination UI must receive a Laravel paginator object, not a plain collection.

## Do Not Invent
- Do not introduce undocumented theme config keys and claim core support for them.
- If a field type or menu behavior is not documented, inspect current code before relying on it.

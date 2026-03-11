---
name: wncms-localization
description: Apply documented WNCMS localization setup for locales, translation overrides, and new language onboarding.
---

## Goal
Change localization behavior using the documented WNCMS locale config and translation override flow.

## When To Use
- adding a new language
- creating `lang/{locale}/custom.php`
- changing locale-related config
- explaining runtime translation behavior

## Read First
- `documentations/manual/developer/locale/localization-overview.md`
- `documentations/manual/developer/locale/translation-files.md`
- `documentations/manual/developer/locale/add-new-language.md`

## Hard Rules
- Site-specific translation overrides belong in `lang/{locale}/custom.php`, not vendor files.
- Prefer merged translation access through `__('wncms::word.key')` or `@lang('wncms::word.key')`.
- New locale support starts by copying `config/laravellocalization.php` into the app and extending `supportedLocales`.
- Each supported locale should have a matching `lang/{locale}/custom.php`.
- Runtime translation settings may be overridden from WNCMS system settings such as `app_locale`, `supported_locales`, and `hide_default_locale_in_url`.

## Do Not Invent
- Do not claim support for locales that are not defined in `supportedLocales`.
- Do not describe undocumented publish commands or locale loading behavior.

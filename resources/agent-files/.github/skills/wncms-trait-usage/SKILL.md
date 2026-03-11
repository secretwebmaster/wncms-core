---
name: wncms-trait-usage
description: Use documented WNCMS trait patterns for custom traits and for built-in multisite, tags, and translations behavior.
---

## Goal
Add or use traits in ways that match documented WNCMS model behavior.

## When To Use
- creating custom traits in `app/Traits`
- working with `HasMultisite`
- working with tags on `BaseModel`
- working with translatable model attributes

## Read First
- `documentations/manual/developer/trait/create-a-trait.md`
- `documentations/manual/developer/trait/has-multisite.md`
- `documentations/manual/developer/trait/has-tags.md`
- `documentations/manual/developer/trait/has-translations.md`

## Hard Rules
- Custom traits should live in `app/Traits/` and follow documented naming such as `Has{Feature}` or `Uses{Behavior}`.
- If a model extends `Wncms\Models\BaseModel`, `HasMultisite` and tag support are already included by default; do not re-add them blindly.
- Website scoping must respect documented website modes: `global`, `single`, and `multi`.
- Tag behavior should use documented tag metas and documented tag methods instead of custom ad hoc conventions.
- Translatable fields must be declared in `$translatable` and accessed through the documented translation APIs.

## Do Not Invent
- Do not claim undocumented trait boot behavior or storage rules.
- If a trait interaction is unclear, inspect the model and trait implementation before changing it.

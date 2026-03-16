---
name: wncms-adding-a-tool
description: Add custom backend Tools page cards from a WNCMS host project or plugin by injecting through documented hooks instead of editing vendor core files.
---

## Goal
Add project-specific or plugin-specific tools to the backend Tools page from the host project without modifying vendor `wncms-core` files.

## When To Use
- adding a custom card to the backend Tools page from `app/` or `public/plugins/`
- wiring a Tools page button to a custom backend route/controller
- localizing a custom tool title, description, or button text

## Read First
- `documentations/manual/developer/event/tools.md`
- `documentations/manual/developer/event/overview.md`
- `documentations/manual/developer/route/add-routes.md`
- `documentations/manual/developer/locale/translation-files.md`

## Hard Rules
- Do not edit vendor `src/Http/Controllers/Backend/ToolController.php` or vendor `resources/views/backend/tools/index.blade.php` from a host project.
- Inject custom tool cards through the documented hook:
  - `wncms.view.backend.tools.index.cards`
- Use `routes/custom_backend.php` for custom backend tool actions.
- Put backend action logic in `app/Http/Controllers/Backend/*`.
- Put user-facing tool text in `lang/{locale}/custom.php` and reference it through `wncms::word.*`.
- Keep each injected tool self-contained and include its own `.col-12.col-md-6.col-lg-3.d-flex` wrapper.
- If the tool performs interactive AJAX behavior, inspect the current project’s existing button/AJAX conventions instead of inventing a new frontend contract blindly.

## Recommended Pattern
1. Register a listener in app or plugin code for `wncms.view.backend.tools.index.cards`.
2. Return rendered HTML for one tool card.
3. If the tool triggers backend behavior, add a route in `routes/custom_backend.php`.
4. Implement the route target in `app/Http/Controllers/Backend/*`.
5. Add translated text keys in `lang/{locale}/custom.php`.

## Example Listener Shape
```php
Event::listen('wncms.view.backend.tools.index.cards', function (): string {
    return view('backend.tools.my-custom-tool')->render();
});
```

## Do Not Invent
- Do not assume undocumented vendor view slots beyond the documented Tools hooks.
- Do not hardcode translated strings into the injected card when `wncms::word.*` keys belong in `lang/{locale}/custom.php`.

---
name: wncms-view-widget-injection
description: Extend backend or frontend views with documented widget injection APIs using registerViewWidget and the @widget directive.
---

## Goal
Inject UI into WNCMS views without editing core blades directly.

## When To Use
- adding dashboard widgets
- injecting fields or panels into backend blades
- extending theme/frontend views with widget slots
- creating reusable view injection keys

## Read First
- `documentations/manual/developer/view/widget-injection.md`

## Hard Rules
- Register widgets with `wncms()->registerViewWidget($key, $view, $data = [])`.
- Render widgets in Blade with `@widget('your.key')`.
- Use descriptive injection keys such as `wncms.backend.admin.dashboard.above_update_log` or `theme.hero.after`.
- Prefer widget injection over editing core WNCMS views directly.
- Keep injected views namespaced, for example `my-package::widgets.dashboard_box`.

## Do Not Invent
- Do not claim undocumented ordering, lifecycle, or storage behavior for widgets.
- If a target injection point does not exist, add a documented `@widget(...)` slot explicitly instead of assuming one is available.

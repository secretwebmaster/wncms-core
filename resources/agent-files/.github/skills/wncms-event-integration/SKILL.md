---
name: wncms-event-integration
description: Consume documented WNCMS events safely by using listed hook names, listener registration patterns, and documented parameter handling.
---

## Goal
Integrate with existing WNCMS events and hooks using only documented event names and payload shapes.

## When To Use
- adding listeners for WNCMS events
- wiring plugin or app behavior to frontend/backend lifecycle hooks
- checking whether a hook is documented before using it

## Read First
- `documentations/manual/developer/event/overview.md`
- `documentations/manual/developer/event/users.md`
- `documentations/manual/developer/event/posts.md`
- `documentations/manual/developer/event/settings.md`
- `documentations/manual/developer/event/themes.md`

## Hard Rules
- Use only documented event names when you need predictable integration points.
- Register listeners through Laravel event registration patterns documented in the manual.
- Respect pass-by-reference parameters such as `&$themeView`, `&$params`, `&$payload`, or `&$result` when the docs mark them mutable.
- Keep hook docs updated in the same task if you add new core hooks.
- Follow existing documented naming and grouping rather than inventing new event families casually.

## Do Not Invent
- Do not assume undocumented hook names or payload parameters.
- If the needed event is not documented, inspect the source before using it.

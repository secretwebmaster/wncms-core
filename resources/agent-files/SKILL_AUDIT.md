# WNCMS Published Skill Audit

This audit is based on English source docs under `documentations/manual` only.

## Manual Review Checklist

- [x] `documentations/manual/index.md`
- [x] `documentations/manual/getting-started/*`
- [x] `documentations/manual/developer/model/*`
- [x] `documentations/manual/developer/controller/*`
- [x] `documentations/manual/developer/manager/*`
- [x] `documentations/manual/developer/route/*`
- [x] `documentations/manual/developer/theme/*`
- [x] `documentations/manual/developer/locale/*`
- [x] `documentations/manual/developer/trait/*`
- [x] `documentations/manual/developer/event/*`
- [x] `documentations/manual/developer/helper/*`
- [x] `documentations/manual/developer/view/*`
- [x] `documentations/manual/developer/plugin/*`
- [x] `documentations/manual/api/*`
- [x] `documentations/manual/package/*`
- [x] `documentations/manual/user/*`

## Boundary

- `/.github/skills` is the maintainer-only skill set for developing `wncms-core`.
- `resources/agent-files/.github/skills` is the published skill set for developers extending WNCMS from their own host project.
- Maintainer-only skills must not be mirrored into the published set unless they are rewritten for host-project usage.

## Maintainer-Only Skills Kept Out Of The Published Set

These remain package-maintainer concerns and are intentionally not published to host projects:

- `wncms-adding-system-settings`
- `wncms-changelog-sync`
- `wncms-doc-deploy`
- `wncms-doc-sync`
- `wncms-migration-structure-policy`

## Published Host-Project Skills

| Skill | Basis In Manual |
| --- | --- |
| `wncms-skill-registration` | meta skill for keeping host-project `AGENTS.md` aligned with local skills |
| `wncms-function-docblocks` | meta skill for keeping host-project PHP function docblocks consistent in Laravel/Breeze style |
| `wncms-adding-a-hook` | `developer/event/*` naming conventions adapted for host-project app/plugin/theme hooks |
| `wncms-coding-style` | WNCMS conventions reused across extensions |
| `wncms-model-creation` | `developer/model/*` |
| `wncms-backend-controller-creation` | `developer/controller/*`, `developer/route/*` |
| `wncms-manager-creation` | `developer/manager/*` |
| `wncms-feature-scaffold` | `developer/model/*`, `developer/controller/*`, `developer/manager/*`, `developer/route/*` |
| `wncms-api-creation` | `developer/controller/api-controller.md`, `developer/route/api.md`, `api/*` |
| `wncms-api-testing` | `api/getting-started.md`, `api/troubleshooting.md` |
| `wncms-plugin-basic-creation` | `developer/plugin/*`, `developer/event/*` |
| `wncms-backend-index-blade` | backend view reuse patterns used by WNCMS UI |
| `wncms-route-customization` | `developer/route/*` |
| `wncms-theme-development` | `developer/theme/*` |
| `wncms-localization` | `developer/locale/*` |
| `wncms-trait-usage` | `developer/trait/*` |
| `wncms-event-integration` | `developer/event/*` |
| `wncms-helper-usage` | `developer/helper/overview.md` |
| `wncms-view-widget-injection` | `developer/view/widget-injection.md` |

## Not Eligible Yet

These docs are empty or too thin for a reliable published skill in this pass:

| Manual Area | Reason |
| --- | --- |
| `developer/resource/*` | empty |
| `developer/cache/overview.md` | empty |
| `developer/config/overview.md` | empty |
| `developer/database/*` | empty |
| most `package/*` pages | empty |

## Notes

- The published skill set now targets host-project paths such as `app/`, `routes/custom_*.php`, `lang/`, `public/plugins/`, and themes.
- No published skill should instruct the agent to build host-project features under `src/` as if it were editing `wncms-core`.
- `wncms-skill-registration` and `wncms-function-docblocks` are intentional meta-skill exceptions; they keep host-project maintenance conventions aligned and are not WNCMS runtime feature skills.
- If a task depends on an undocumented or empty manual area, inspect code directly before making claims.

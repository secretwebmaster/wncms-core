# WNCMS Project AI Guide (Published To Host Projects)

## Scope
This file is the canonical instruction set for AI agents working inside a Laravel project that uses `secretwebmaster/wncms-core`.

This file is **not** for maintaining the `wncms-core` package itself.

## Skill Set Boundary
- `/.github/skills` in the `wncms-core` package is for the maintainer of `wncms-core`.
- `resources/agent-files/.github/skills` is the publishable skill set for developers building their own WNCMS-based projects.
- Publishable skills should guide agents to extend WNCMS from the host project using `app/`, `routes/custom_*.php`, `lang/`, `public/plugins/`, and themes.
- Do not copy maintainer-only workflows such as core changelog management or docs deployment into the published skill set.

## Required Architecture Rules
- New app models should be placed in `app/Models/{Name}.php`.
- New app models should extend `Wncms\Models\BaseModel`.
- Every new app model must define `public static $modelKey = '{snake_case_singular}';`.
- New backend app controllers should extend `Wncms\Http\Controllers\Backend\BackendController`.
- New frontend app controllers should extend `Wncms\Http\Controllers\Frontend\FrontendController`.
- New API app controllers should extend `Wncms\Http\Controllers\Api\V1\ApiController`.
- New app managers should extend `Wncms\Services\Managers\ModelManager` and live under `app/Services/Managers/{Name}Manager.php`.
- Model relations should prefer `wncms()->getModelClass('{model_key}')` instead of hardcoded class names.
- Custom backend routes should go to `routes/custom_backend.php`.
- Custom frontend routes should go to `routes/custom_frontend.php`.
- Custom API routes should go to `routes/custom_api.php`.
- After backend mutations in custom backend controllers, invalidate cache with `$this->flush()` when using `BackendController` patterns.

## Skills To Apply
- `.github/skills/wncms-coding-style/SKILL.md`
- `.github/skills/wncms-function-docblocks/SKILL.md`
- `.github/skills/wncms-skill-registration/SKILL.md`
- `.github/skills/wncms-adding-a-hook/SKILL.md`
- `.github/skills/wncms-adding-a-tool/SKILL.md`
- `.github/skills/wncms-model-creation/SKILL.md`
- `.github/skills/wncms-backend-controller-creation/SKILL.md`
- `.github/skills/wncms-manager-creation/SKILL.md`
- `.github/skills/wncms-feature-scaffold/SKILL.md`
- `.github/skills/wncms-api-creation/SKILL.md`
- `.github/skills/wncms-api-testing/SKILL.md`
- `.github/skills/wncms-plugin-basic-creation/SKILL.md`
- `.github/skills/wncms-backend-index-blade/SKILL.md`
- `.github/skills/wncms-route-customization/SKILL.md`
- `.github/skills/wncms-theme-development/SKILL.md`
- `.github/skills/wncms-localization/SKILL.md`
- `.github/skills/wncms-trait-usage/SKILL.md`
- `.github/skills/wncms-event-integration/SKILL.md`
- `.github/skills/wncms-helper-usage/SKILL.md`
- `.github/skills/wncms-adding-system-settings/SKILL.md`
- `.github/skills/wncms-view-widget-injection/SKILL.md`

## Skill Routing
- For adding or normalizing PHP function docblocks in full PHPDoc style for Laravel code, apply:
  - `.github/skills/wncms-function-docblocks/SKILL.md`
- For creating, deleting, renaming, or repurposing project-local skills under `/.github/skills`, apply:
  - `.github/skills/wncms-skill-registration/SKILL.md`
- For adding new custom hooks or reviewing hook naming in app/plugin/theme code, apply:
  - `.github/skills/wncms-adding-a-hook/SKILL.md`
- For adding project-specific backend Tools page cards through documented hooks, apply:
  - `.github/skills/wncms-adding-a-tool/SKILL.md`
- For local app models in `app/Models`, apply:
  - `.github/skills/wncms-model-creation/SKILL.md`
- For backend CRUD controllers in `app/Http/Controllers/Backend`, apply:
  - `.github/skills/wncms-backend-controller-creation/SKILL.md`
- For local managers in `app/Services/Managers`, apply:
  - `.github/skills/wncms-manager-creation/SKILL.md`
- For a complete host-project feature scaffold, apply:
  - `.github/skills/wncms-feature-scaffold/SKILL.md`
- For app JSON APIs and custom API controllers, apply:
  - `.github/skills/wncms-api-creation/SKILL.md`
- For verifying API requests or troubleshooting API access, apply:
  - `.github/skills/wncms-api-testing/SKILL.md`
- For plugins under `public/plugins`, apply:
  - `.github/skills/wncms-plugin-basic-creation/SKILL.md`
- For backend index Blade tables, apply:
  - `.github/skills/wncms-backend-index-blade/SKILL.md`
- For route work in `routes/custom_*.php`, apply:
  - `.github/skills/wncms-route-customization/SKILL.md`
- For themes, menus, page templates, and pagination, apply:
  - `.github/skills/wncms-theme-development/SKILL.md`
- For locales, `lang/{locale}/custom.php`, and adding languages, apply:
  - `.github/skills/wncms-localization/SKILL.md`
- For traits and documented multisite/tags/translations behavior, apply:
  - `.github/skills/wncms-trait-usage/SKILL.md`
- For listening to existing documented hooks, apply:
  - `.github/skills/wncms-event-integration/SKILL.md`
- For helper migration, `gss()`, `uss()`, and plugin loader usage, apply:
  - `.github/skills/wncms-helper-usage/SKILL.md`
- For adding host-project or plugin system settings without editing vendor files, apply:
  - `.github/skills/wncms-adding-system-settings/SKILL.md`
- For `registerViewWidget()` and `@widget()` based UI extension, apply:
  - `.github/skills/wncms-view-widget-injection/SKILL.md`

## Default Behavior For "Create X Model"
When asked to create an entity such as `Article` in a host project, prefer local project scaffolding unless the user explicitly asks for package-internal files.

Minimum expected output in a host project:
1. `app/Models/Article.php` extending `BaseModel` with `$modelKey = 'article'`.
2. `app/Http/Controllers/Backend/ArticleController.php` extending `BackendController`.
3. `app/Services/Managers/ArticleManager.php` extending `ModelManager` when custom manager logic is needed.
4. Migration creating `articles` table.
5. Backend routes in `routes/custom_backend.php` with `article_*` permissions.
6. Backend views under `resources/views/backend/articles/`.

If any item is skipped, explicitly state why.

## How To Prompt AI In A Host Project
Use this prompt frame to get consistent results:

```text
Read and follow AGENTS.md and all skill files listed in it.
Task: <your task>
Constraints:
- Extend WNCMS from the host project.
- Use app-level paths (`app/`, `routes/custom_*.php`, `lang/`, `public/plugins/`) unless package-internal edits are explicitly requested.
- Use WNCMS patterns (BaseModel, BackendController, ModelManager, ApiController, FrontendController).
- List files changed and why.
- If skipping scaffold pieces, explain what was skipped.
```

## Documented-Only Rule
- The published WNCMS feature-extension skills in `.github/skills` are limited to behavior explicitly documented in non-empty files under `documentations/manual`.
- Exception: `.github/skills/wncms-skill-registration/SKILL.md` and `.github/skills/wncms-function-docblocks/SKILL.md` are project-maintenance skills. They do not describe WNCMS runtime behavior.
- Do not invent behavior for empty or placeholder docs such as:
  - `documentations/manual/developer/resource/*`
  - `documentations/manual/developer/cache/overview.md`
  - `documentations/manual/developer/config/overview.md`
  - `documentations/manual/developer/database/*`
  - most empty `documentations/manual/package/*` pages
- If a task depends on one of those areas, state that the current manual is not sufficient and inspect the code directly before making claims.

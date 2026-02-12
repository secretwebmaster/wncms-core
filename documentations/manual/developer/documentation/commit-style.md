# Commit Style Guide

This project follows the **Conventional Commits** standard.

## Format

```
<type>: <short description>
```

- All lowercase
- No period at the end
- Short and clear

## Common Types

- **feat** – new feature
- **fix** – bug fix
- **update** – minor update or enhancement
- **improve** – general improvements without new features
- **refactor** – code restructuring
- **style** – formatting or UI-only changes
- **docs** – documentation changes
- **chore** – maintenance tasks
- **perf** – performance improvements
- **test** – related to tests

## Examples

```
fix: resolve smtp dsn error when mail config is empty
feat: add gallery field to page templates
update: improve frontend pagination styles
refactor: unify model manager logic
docs: add tag filter usage examples
```

## Rules

- Keep messages short and descriptive.
- Use the correct type to make history readable.
- One logical change per commit.
- English only.

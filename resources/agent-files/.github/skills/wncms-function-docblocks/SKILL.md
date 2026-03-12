---
name: wncms-function-docblocks
description: Add or normalize PHP function docblocks in full PHPDoc style for WNCMS-based Laravel host projects.
---

## Goal
Keep host-project function and method docblocks explicit, consistent, and readable in a full PHPDoc style for Laravel code.

## Use When
- adding missing docblocks above PHP methods or functions
- normalizing inconsistent docblock formatting
- updating controller, service, manager, model, or command method comments

## Hard Rules
- Use block comments only above functions or methods.
- Keep the first line a short sentence summary, for example:
  - `Display the registration view.`
  - `Handle an incoming registration request.`
- Use the standard PHPDoc opening and closing form:
```php
/**
 * Summary.
 */
```
- Insert a blank `*` line between the summary and any tags.
- Use fully qualified class names in tags, matching Laravel stub style.
- Prefer these tags when relevant:
  - `@param`
  - `@return`
  - `@throws`
- Add a short behavior paragraph when the method has meaningful side effects, fallback behavior, or non-obvious flow.
- Do not add block comments inside function bodies; use `//` only when an inline comment is truly needed.
- Preserve existing accurate docblocks; only rewrite when they are missing, inconsistent, or misleading.

## Style Guide
- Public controller actions should usually have:
  - summary
  - one short behavior paragraph when useful
  - `@param` when there is a request object or other meaningful input
  - `@return`
  - `@throws` only when relevant
- Simple helper methods should still use full PHPDoc when they are part of the class contract:
  - summary
  - one short behavior paragraph if the method affects flow or configuration
  - `@return` only
- `void` methods should explicitly use `@return void`.
- Use union return types in `@return` when the method can respond in multiple ways.
- Avoid redundant wording like:
  - `This function will...`
  - `Method to handle...`

## Example
```php
/**
 * Handle an incoming registration request.
 *
 * Validates the request and creates a new user account for the current website.
 *
 * @param  \Illuminate\Http\Request  $request
 *
 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
 *
 * @throws \Illuminate\Validation\ValidationException
 */
public function store(Request $request)
```

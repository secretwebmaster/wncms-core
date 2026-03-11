---
name: wncms-coding-style
description: Enforce WNCMS coding conventions for PHP/Laravel/Blade and WNCMS-specific rules.
---

## Goal
Apply WNCMS project conventions consistently when generating or modifying code.

## Hard rules
- Do not use emojis.
- Do not use heredoc`<<<EOT`. Use string concatenation `$msg .=` instead when building multi-line text.
- Array arrow spacing must be exactly one space on both sides: `=>`.
- Preserve existing comments and structure unless required for correctness.
- Inside functions: use single-line `//` comments only. Avoid redundant comments.
- Block comments are allowed only above functions as documentation.
- Blade HTML attributes of the same element must be inline on a single line and never wrapped across multiple lines.

## WNCMS routing rules
- Define frontend routes without `frontend.`, and always reference them with `frontend.` when using route helpers.

## WNCMS translation rules
- For translatable labels/messages, use `__('wncms::word.{key}')`.
- Before using a `wncms::word.{key}` translation key, verify the key exists in all default locale files:
  - `lang/en/word.php`
  - `lang/zh_CN/word.php`
  - `lang/zh_TW/word.php`
  - `lang/ja/word.php`
- If the key is missing in any default locale file, add it in the same task so all locales stay in sync.
- For plugin namespace keys (for example `{plugin_id}::word.{key}`), maintain the same default locale coverage: `en`, `zh_CN`, `zh_TW`, `ja`.

# Settings UI Notes

## System Tab Layout

- In desktop view (`lg` and above), the **System** settings tab renders basic fields in a 2-column layout.
- Spacing is reduced for a denser form layout (`p-3 p-lg-4` with smaller row gaps).

## Multisite Website Selector

- For model forms in **multi** website mode, website checkboxes are shown full width.
- Each domain is rendered on its own row for clearer scanning.

## Multisite Tab Layout

- In Settings `multisite` tab, model website modes are rendered as a 2-column grid in the right panel (`col-lg-8`), not as a table.
- Each item shows inline model label + mode select (`global` / `single` / `multi`).

## Mobile Tab Navigation

- Settings tab navigation is now single-line on mobile.
- Tabs no longer wrap; they are horizontally scrollable.
- Scrollbar is hidden for a cleaner mobile layout.
- The nav auto-scrolls to bring the active tab into view on page load and tab switch.

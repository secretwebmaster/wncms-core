# Theme Page Templates

This page defines the recommended `templates` format in `resources/themes/{theme_id}/config.php` for backend template options and frontend template rendering.

## Template Config Shape

Use this structure:

```php
'templates' => [
    'testing' => [
        'label' => 'Testing Template',

        // Compatibility keys for current PageManager::createDefaultThemeTemplatePages
        'slug' => 'testing',
        'title' => 'Testing Template',
        'blade_name' => 'testing',

        'sections' => [
            'switch_test' => [
                'label' => 'Type Switch Test',
                'options' => [
                    ['label' => 'Type Switch Test', 'type' => 'heading'],
                    ['label' => 'Switch Target', 'name' => 'switch_target', 'type' => 'gallery'],
                ],
            ],
        ],
    ],
],
```

## How It Is Used

1. Backend page edit (`Backend\\PageController@edit`) reads `config("theme.{theme}.templates.{blade_name}.sections")` to render template option fields.
2. Backend page update (`Backend\\PageController@update`) uses the same field map to normalize and save values.
3. Frontend page show (`Frontend\\PageController@show`) renders:
   - `{$themeId}::pages.templates.{blade_name}`
4. `Page::option('{section}.{field}')` reads template option values.

## Required Blade File

Each template key should have a matching Blade file:

- Config key: `templates.testing`
- Page `blade_name`: `testing`
- Blade path: `resources/themes/{theme_id}/views/pages/templates/testing.blade.php`

## Array -> Text Type Switching

When changing a field type from array-like (`gallery`, `accordion`) to scalar (`text`, `textarea`):

- Existing JSON data is preserved and shown as a string in edit form.
- Saving after editing converts/stores it as plain string.
- This behavior is handled in `Backend\\PageController@edit` value normalization.

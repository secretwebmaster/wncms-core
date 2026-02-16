# Themes Events

## Frontend Theme Lifecycle

#### `wncms.frontend.themes.boot.before`

Dispatched before theme boot begins for the current website.

Parameters:
- `&$themeId` (string)
- `$website` (Website model)

#### `wncms.frontend.themes.load.before`

Dispatched before a theme path is loaded. You can override the theme ID or path by reference.

Parameters:
- `&$themeId` (string)
- `&$themePath` (?string)
- `$website` (Website model)

#### `wncms.frontend.themes.load.after`

Dispatched after theme config/views/translations/functions are loaded.

Parameters:
- `$themeId` (string)
- `$themePath` (string)
- `$website` (Website model)

#### `wncms.frontend.themes.boot.after`

Dispatched after theme boot completes.

Parameters:
- `$themeId` (string)
- `$website` (Website model)

## Example Listener

```php
use Illuminate\Support\Facades\Event;

Event::listen('wncms.frontend.themes.load.before', function (&$themeId, &$themePath, $website) {
    // Example: force default theme for a specific domain
    if (($website?->domain ?? '') === 'example.com') {
        $themeId = 'default';
        $themePath = public_path('themes/default');
    }
});
```

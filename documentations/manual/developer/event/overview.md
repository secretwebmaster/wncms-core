# Event System Overview

## Introduction

WNCMS event docs are grouped by domain. Add new hooks to the matching group page when introducing them in core.

## Event Groups

- [Users Events](./users.md)
- [Posts Events](./posts.md)
- [Settings Events](./settings.md)
- [Themes Events](./themes.md)

## Event Registration

### In a Service Provider

```php
namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::listen('wncms.frontend.users.register.after', function ($user) {
            // Your logic here
        });
    }
}
```

### Using Event Listeners

```php
namespace App\Listeners;

class SendWelcomeEmail
{
    public function handle($user)
    {
        // Send welcome email
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    'wncms.frontend.users.register.after' => [
        SendWelcomeEmail::class,
    ],
];
```

## Best Practices

1. Use references when runtime mutation is required (`&$param`).
2. Keep hook names aligned with the naming standard.
3. Wrap listener logic in error handling when side effects are non-critical.
4. Keep hook docs updated in this folder in the same task.
5. Sync zh-CN and zh-TW docs when structure changes.

## See Also

- [Laravel Events Documentation](https://laravel.com/docs/events)
- [Service Providers](../overview.md)
- [Frontend Controller](../controller/frontend-controller.md)

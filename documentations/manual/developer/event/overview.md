# Event System Overview

## Introduction

WNCMS provides a comprehensive event system that allows you to hook into various lifecycle events. This enables you to extend functionality without modifying core files.

## User Lifecycle Events

### Frontend User Events

#### wncms.frontend.users.dashboard

Triggered when displaying the user dashboard page.

**Parameters:**

- `&$themeView` (string): The theme view path
- `&$params` (array): View parameters
- `&$defaultView` (string): Default fallback view

**Example:**

```php
Event::listen('wncms.frontend.users.dashboard', function(&$themeView, &$params, &$defaultView) {
    // Add custom data to dashboard
    $params['customData'] = 'Custom dashboard data';
});
```

#### wncms.frontend.users.show_login

Triggered when displaying the login page.

**Parameters:**

- `&$themeView` (string): The theme view path
- `&$params` (array): View parameters
- `&$defaultView` (string): Default fallback view
- `&$loggedInRedirectRouteName` (string): Route name to redirect after login

**Example:**

```php
Event::listen('wncms.frontend.users.show_login', function(&$themeView, &$params, &$defaultView, &$loggedInRedirectRouteName) {
    // Redirect to custom page after login
    $loggedInRedirectRouteName = 'frontend.custom.dashboard';
});
```

#### wncms.frontend.users.show_register

Triggered when displaying the registration page.

**Parameters:**

- `&$themeView` (string): The theme view path
- `&$params` (array): View parameters
- `&$defaultView` (string): Default fallback view
- `&$disabledRegistrationRedirectRouteName` (string): Redirect route if registration is disabled

**Example:**

```php
Event::listen('wncms.frontend.users.show_register', function(&$themeView, &$params, &$defaultView, &$disabledRegistrationRedirectRouteName) {
    // Add terms and conditions to registration page
    $params['termsUrl'] = route('frontend.pages.terms');
});
```

#### wncms.frontend.users.register

Triggered when a user submits registration form (before validation).

**Parameters:**

- `&$disabledRegistrationRedirectRouteName` (string): Redirect route if disabled
- `&$sendWelcomeEmail` (bool): Whether to send welcome email
- `&$defaultUserRoles` (string): Default roles to assign (comma-separated)
- `&$redirectAfterRegister` (string|null): Custom redirect URL after registration
- `&$intendedUrl` (string|null): Intended URL from session

**Example:**

```php
Event::listen('wncms.frontend.users.register', function(
    &$disabledRegistrationRedirectRouteName,
    &$sendWelcomeEmail,
    &$defaultUserRoles,
    &$redirectAfterRegister,
    &$intendedUrl
) {
    // Enable welcome emails
    $sendWelcomeEmail = true;

    // Assign custom role
    $defaultUserRoles = 'subscriber,verified';

    // Redirect to custom onboarding
    $redirectAfterRegister = route('frontend.onboarding.welcome');
});
```

#### wncms.frontend.users.registered

Triggered after a user has been successfully created.

**Parameters:**

- `$user` (User): The newly created user model

**Example:**

```php
Event::listen('wncms.frontend.users.registered', function($user) {
    // Send notification to admin
    Notification::route('mail', 'admin@example.com')
        ->notify(new NewUserRegistered($user));

    // Log registration
    Log::info('New user registered', ['user_id' => $user->id]);
});
```

#### wncms.frontend.users.registered.credits

Triggered after user registration for credit system initialization.

**Parameters:**

- `$user` (User): The newly created user model

**Example:**

```php
Event::listen('wncms.frontend.users.registered.credits', function($user) {
    // Initialize credits for new user
    $user->credits()->create(['type' => 'balance', 'amount' => 0]);
    $user->credits()->create(['type' => 'points', 'amount' => 100]); // Welcome bonus
});
```

#### wncms.frontend.users.registered.welcome_email

Triggered to handle welcome email after registration.

**Parameters:**

- `$user` (User): The newly created user model
- `$sendWelcomeEmail` (bool): Whether to send the email

**Example:**

```php
Event::listen('wncms.frontend.users.registered.welcome_email', function($user, $sendWelcomeEmail) {
    if ($sendWelcomeEmail) {
        Mail::to($user->email)->send(new WelcomeEmail($user));
    }
});
```

#### wncms.frontend.users.logout.before

Triggered before a user logs out.

**Parameters:**

- `$user` (User|null): The current authenticated user

**Example:**

```php
Event::listen('wncms.frontend.users.logout.before', function($user) {
    if ($user) {
        // Log logout activity
        Activity::log([
            'user_id' => $user->id,
            'action' => 'logout',
            'ip' => request()->ip(),
        ]);
    }
});
```

#### wncms.frontend.users.logout.after

Triggered after a user has logged out.

**Parameters:**
None

**Example:**

```php
Event::listen('wncms.frontend.users.logout.after', function() {
    // Clear session data
    session()->forget('custom_data');
});
```

## Event Registration

### In a Service Provider

Register events in your service provider's `boot()` method:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::listen('wncms.frontend.users.registered', function($user) {
            // Your logic here
        });
    }
}
```

### Using Event Listeners

Create a dedicated listener class:

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
    'wncms.frontend.users.registered' => [
        SendWelcomeEmail::class,
    ],
];
```

## Best Practices

1. **Use References**: When modifying parameters, ensure they're passed by reference (`&$param`)
2. **Type Safety**: Check parameter types before modification
3. **Error Handling**: Wrap event logic in try-catch to prevent breaking the flow
4. **Documentation**: Document your event listeners for team members
5. **Testing**: Test events in isolation and integration

## See Also

- [Laravel Events Documentation](https://laravel.com/docs/events)
- [Service Providers](../overview.md)
- [Frontend Controller](../controller/frontend-controller.md)

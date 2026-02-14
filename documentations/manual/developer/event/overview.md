# Event System Overview

## Introduction

WNCMS provides a comprehensive event system that allows you to hook into various lifecycle events. This enables you to extend functionality without modifying core files.

## User Lifecycle Events

### Frontend User Events

#### wncms.frontend.users.dashboard.resolve

Triggered when displaying the user dashboard page.

**Parameters:**

- `&$themeView` (string): The theme view path
- `&$params` (array): View parameters
- `&$defaultView` (string): Default fallback view

**Example:**

```php
Event::listen('wncms.frontend.users.dashboard.resolve', function(&$themeView, &$params, &$defaultView) {
    // Add custom data to dashboard
    $params['customData'] = 'Custom dashboard data';
});
```

#### wncms.frontend.users.login.resolve

Triggered when displaying the login page.

**Parameters:**

- `&$themeView` (string): The theme view path
- `&$params` (array): View parameters
- `&$defaultView` (string): Default fallback view
- `&$loggedInRedirectRouteName` (string): Route name to redirect after login

**Example:**

```php
Event::listen('wncms.frontend.users.login.resolve', function(&$themeView, &$params, &$defaultView, &$loggedInRedirectRouteName) {
    // Redirect to custom page after login
    $loggedInRedirectRouteName = 'frontend.custom.dashboard';
});
```

#### wncms.frontend.users.login.before

Triggered before login validation.

**Parameters:**

- `$request` (Request)
- `&$rules` (array)
- `&$messages` (array)

#### wncms.frontend.users.login.after

Triggered after login succeeds and before redirect.

**Parameters:**

- `$user` (User)
- `$request` (Request)
- `&$redirectUrl` (string)

#### wncms.frontend.users.register.resolve

Triggered when displaying the registration page.

**Parameters:**

- `&$themeView` (string): The theme view path
- `&$params` (array): View parameters
- `&$defaultView` (string): Default fallback view
- `&$disabledRegistrationRedirectRouteName` (string): Redirect route if registration is disabled

**Example:**

```php
Event::listen('wncms.frontend.users.register.resolve', function(&$themeView, &$params, &$defaultView, &$disabledRegistrationRedirectRouteName) {
    // Add terms and conditions to registration page
    $params['termsUrl'] = route('frontend.pages.terms');
});
```

#### wncms.frontend.users.register.before

Triggered when a user submits registration form (before validation).

**Parameters:**

- `&$disabledRegistrationRedirectRouteName` (string): Redirect route if disabled
- `&$sendWelcomeEmail` (bool): Whether to send welcome email
- `&$defaultUserRoles` (string): Default roles to assign (comma-separated)
- `&$redirectAfterRegister` (string|null): Custom redirect URL after registration
- `&$intendedUrl` (string|null): Intended URL from session

**Example:**

```php
Event::listen('wncms.frontend.users.register.before', function(
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

#### wncms.frontend.users.register.after

Triggered after a user has been successfully created.

**Parameters:**

- `$user` (User): The newly created user model

**Example:**

```php
Event::listen('wncms.frontend.users.register.after', function($user) {
    // Send notification to admin
    Notification::route('mail', 'admin@example.com')
        ->notify(new NewUserRegistered($user));

    // Log registration
    Log::info('New user registered', ['user_id' => $user->id]);
});
```

#### wncms.frontend.users.register.credits.after

Triggered after user registration for credit system initialization.

**Parameters:**

- `$user` (User): The newly created user model

**Example:**

```php
Event::listen('wncms.frontend.users.register.credits.after', function($user) {
    // Initialize credits for new user
    $user->credits()->create(['type' => 'balance', 'amount' => 0]);
    $user->credits()->create(['type' => 'points', 'amount' => 100]); // Welcome bonus
});
```

#### wncms.frontend.users.register.welcome_email.after

Triggered to handle welcome email after registration.

**Parameters:**

- `$user` (User): The newly created user model
- `$sendWelcomeEmail` (bool): Whether to send the email

**Example:**

```php
Event::listen('wncms.frontend.users.register.welcome_email.after', function($user, $sendWelcomeEmail) {
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

#### wncms.frontend.users.auth.after

Triggered after frontend auth is successful.

**Parameters:**

- `$user` (User): Authenticated user model

#### wncms.frontend.users.profile.show.resolve

Triggered when displaying profile page.

**Parameters:**

- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)

#### wncms.frontend.users.profile.edit.resolve

Triggered when displaying profile edit page.

**Parameters:**

- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)

#### wncms.frontend.users.profile.update.before

Triggered before frontend profile update persistence.

**Parameters:**

- `$user` (User)
- `$request` (Request)
- `&$attributes` (array)

#### wncms.frontend.users.profile.update.after

Triggered after frontend profile update persistence.

**Parameters:**

- `$user` (User)
- `$request` (Request)

#### wncms.frontend.users.password.forgot.resolve

Triggered when displaying forgot password page.

**Parameters:**

- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)

#### wncms.frontend.users.password.forgot.before

Triggered before forgot password form validation.

**Parameters:**

- `$request` (Request)
- `&$rules` (array)
- `&$messages` (array)

#### wncms.frontend.users.password.forgot.after

Triggered after forgot password token/notification flow succeeds.

**Parameters:**

- `$user` (User)
- `$request` (Request)

#### wncms.frontend.users.password.reset.resolve

Triggered when displaying reset password page.

**Parameters:**

- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)

#### wncms.frontend.users.password.reset.before

Triggered before reset password form validation.

**Parameters:**

- `$request` (Request)
- `&$rules` (array)
- `&$messages` (array)

#### wncms.frontend.users.password.reset.after

Triggered after reset password flow returns status.

**Parameters:**

- `$user` (User|null)
- `$request` (Request)
- `$status` (string)

### Backend Users Account Events

#### wncms.backend.users.account.profile.resolve

Triggered before rendering backend account profile view.

**Parameters:**

- `&$view` (string)
- `&$params` (array)

#### wncms.backend.users.account.profile.update.before

Triggered before backend account profile update.

**Parameters:**

- `$user` (User)
- `$request` (Request)
- `&$attributes` (array)

#### wncms.backend.users.account.profile.update.after

Triggered after backend account profile update.

**Parameters:**

- `$user` (User)
- `$request` (Request)

#### wncms.backend.users.account.email.update.before

Triggered before backend account email update.

**Parameters:**

- `$user` (User)
- `$request` (Request)

#### wncms.backend.users.account.email.update.after

Triggered after backend account email update.

**Parameters:**

- `$user` (User)
- `$request` (Request)

#### wncms.backend.users.account.password.update.before

Triggered before backend account password update.

**Parameters:**

- `$user` (User)
- `$request` (Request)

#### wncms.backend.users.account.password.update.after

Triggered after backend account password update.

**Parameters:**

- `$user` (User)
- `$request` (Request)

### Backend Users CRUD and View Slot Events

#### wncms.backend.users.index.query.before

Triggered before backend users index query is finalized.

**Parameters:**

- `$request` (Request)
- `&$q` (Eloquent\Builder)

#### wncms.backend.users.create.resolve

Triggered before rendering backend users create page.

**Parameters:**

- `&$view` (string)
- `&$params` (array)

#### wncms.backend.users.edit.resolve

Triggered before rendering backend users edit page.

**Parameters:**

- `&$view` (string)
- `&$params` (array)

#### wncms.backend.users.store.before

Triggered before backend users store validation.

**Parameters:**

- `$request` (Request)
- `&$rules` (array)
- `&$messages` (array)

#### wncms.backend.users.store.attributes.before

Triggered before backend users store persistence.

**Parameters:**

- `$request` (Request)
- `&$attributes` (array)

#### wncms.backend.users.store.after

Triggered after backend users store persistence.

**Parameters:**

- `$user` (User)
- `$request` (Request)

#### wncms.backend.users.update.before

Triggered before backend users update validation.

**Parameters:**

- `$user` (User)
- `$request` (Request)
- `&$rules` (array)
- `&$messages` (array)

#### wncms.backend.users.update.attributes.before

Triggered before backend users update persistence.

**Parameters:**

- `$user` (User)
- `$request` (Request)
- `&$attributes` (array)

#### wncms.backend.users.update.after

Triggered after backend users update persistence.

**Parameters:**

- `$user` (User)
- `$request` (Request)

#### wncms.view.backend.users.create.fields

View slot event for injecting fields into backend users create form.

**Parameters:**

- `$request` (Request)

#### wncms.view.backend.users.edit.fields

View slot event for injecting fields into backend users edit form.

**Parameters:**

- `$user` (User)
- `$request` (Request)

#### wncms.view.backend.users.index.columns.header

View slot event for injecting header columns into backend users index table.

**Parameters:**

- `$request` (Request)

#### wncms.view.backend.users.index.columns.row

View slot event for injecting row columns into backend users index table.

**Parameters:**

- `$user` (User)
- `$request` (Request)

#### wncms.view.frontend.users.profile.show.fields

View slot event for injecting rows into frontend users profile show table.

**Parameters:**

- `$user` (User)

### Backend Settings Events

#### wncms.backend.settings.tabs.extend

Triggered before backend settings tabs are rendered, allowing plugins to inject custom tabs and fields.

**Parameters:**

- `&$availableSettings` (array)
- `$settings` (array)
- `$request` (Request)

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
        Event::listen('wncms.frontend.users.register.after', function($user) {
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
    'wncms.frontend.users.register.after' => [
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

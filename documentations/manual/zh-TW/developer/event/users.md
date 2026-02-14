# Users Events

## Frontend Users

#### wncms.frontend.users.dashboard.resolve

Parameters:
- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)

#### wncms.frontend.users.login.resolve

Parameters:
- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)
- `&$loggedInRedirectRouteName` (string)

#### wncms.frontend.users.login.before

Parameters:
- `$request` (Request)
- `&$rules` (array)
- `&$messages` (array)

#### wncms.frontend.users.login.after

Parameters:
- `$user` (User)
- `$request` (Request)
- `&$redirectUrl` (string)

#### wncms.frontend.users.register.resolve

Parameters:
- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)
- `&$disabledRegistrationRedirectRouteName` (string)

#### wncms.frontend.users.register.before

Parameters:
- `&$disabledRegistrationRedirectRouteName` (string)
- `&$sendWelcomeEmail` (bool)
- `&$defaultUserRoles` (string)
- `&$redirectAfterRegister` (string|null)
- `&$intendedUrl` (string|null)

#### wncms.frontend.users.register.after

Parameters:
- `$user` (User)

#### wncms.frontend.users.register.credits.after

Parameters:
- `$user` (User)

#### wncms.frontend.users.register.welcome_email.after

Parameters:
- `$user` (User)
- `$sendWelcomeEmail` (bool)

#### wncms.frontend.users.logout.before

Parameters:
- `$user` (User|null)

#### wncms.frontend.users.logout.after

Parameters:
- None

#### wncms.frontend.users.auth.after

Parameters:
- `$user` (User)

#### wncms.frontend.users.profile.show.resolve

Parameters:
- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)

#### wncms.frontend.users.profile.edit.resolve

Parameters:
- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)

#### wncms.frontend.users.profile.update.before

Parameters:
- `$user` (User)
- `$request` (Request)
- `&$attributes` (array)

#### wncms.frontend.users.profile.update.after

Parameters:
- `$user` (User)
- `$request` (Request)

#### wncms.frontend.users.password.forgot.resolve

Parameters:
- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)

#### wncms.frontend.users.password.forgot.before

Parameters:
- `$request` (Request)
- `&$rules` (array)
- `&$messages` (array)

#### wncms.frontend.users.password.forgot.after

Parameters:
- `$user` (User)
- `$request` (Request)

#### wncms.frontend.users.password.reset.resolve

Parameters:
- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)

#### wncms.frontend.users.password.reset.before

Parameters:
- `$request` (Request)
- `&$rules` (array)
- `&$messages` (array)

#### wncms.frontend.users.password.reset.after

Parameters:
- `$user` (User|null)
- `$request` (Request)
- `$status` (string)

## Backend Users Account

#### wncms.backend.users.account.profile.resolve

Parameters:
- `&$view` (string)
- `&$params` (array)

#### wncms.backend.users.account.profile.update.before

Parameters:
- `$user` (User)
- `$request` (Request)
- `&$attributes` (array)

#### wncms.backend.users.account.profile.update.after

Parameters:
- `$user` (User)
- `$request` (Request)

#### wncms.backend.users.account.email.update.before

Parameters:
- `$user` (User)
- `$request` (Request)

#### wncms.backend.users.account.email.update.after

Parameters:
- `$user` (User)
- `$request` (Request)

#### wncms.backend.users.account.password.update.before

Parameters:
- `$user` (User)
- `$request` (Request)

#### wncms.backend.users.account.password.update.after

Parameters:
- `$user` (User)
- `$request` (Request)

## Backend Users CRUD + View Slots

#### wncms.backend.users.index.query.before

Parameters:
- `$request` (Request)
- `&$q` (Eloquent\Builder)

#### wncms.backend.users.create.resolve

Parameters:
- `&$view` (string)
- `&$params` (array)

#### wncms.backend.users.edit.resolve

Parameters:
- `&$view` (string)
- `&$params` (array)

#### wncms.backend.users.store.before

Parameters:
- `$request` (Request)
- `&$rules` (array)
- `&$messages` (array)

#### wncms.backend.users.store.attributes.before

Parameters:
- `$request` (Request)
- `&$attributes` (array)

#### wncms.backend.users.store.after

Parameters:
- `$user` (User)
- `$request` (Request)

#### wncms.backend.users.update.before

Parameters:
- `$user` (User)
- `$request` (Request)
- `&$rules` (array)
- `&$messages` (array)

#### wncms.backend.users.update.attributes.before

Parameters:
- `$user` (User)
- `$request` (Request)
- `&$attributes` (array)

#### wncms.backend.users.update.after

Parameters:
- `$user` (User)
- `$request` (Request)

#### wncms.view.backend.users.create.fields

Parameters:
- `$request` (Request)

#### wncms.view.backend.users.edit.fields

Parameters:
- `$user` (User)
- `$request` (Request)

#### wncms.view.backend.users.index.columns.header

Parameters:
- `$request` (Request)

#### wncms.view.backend.users.index.columns.row

Parameters:
- `$user` (User)
- `$request` (Request)

#### wncms.view.frontend.users.profile.show.fields

Parameters:
- `$user` (User)

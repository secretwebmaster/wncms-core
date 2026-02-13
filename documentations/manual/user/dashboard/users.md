# Users

Backend user management now enforces unique `username` and unique `email` during create and edit actions.

## Where It Applies

- Backend create: `POST /panel/users/store` (`users.store`, permission `user_create`)
- Backend update: `PATCH /panel/users/{id}` (`users.update`, permission `user_edit`)
- Controller: `src/Http/Controllers/Backend/UserController.php`

## Validation Behavior

- `username` is required and must be unique in `users.username`.
- `email` is required, must be a valid email, and must be unique in `users.email`.
- In edit mode, current user record is excluded from uniqueness checks.

Validation messages use:

- `wncms::word.username_has_been_used`
- `wncms::word.email_has_been_used`

## Practical Example

If user `A` already has `username=alex`, creating or editing another user to `alex` will fail validation and return an error message instead of saving duplicate data.

## Frontend Registration Email Validation

Frontend registration now prevents non-email values from being stored in the `email` field.

## Where It Applies

- Frontend register submit: `POST /user/register/submit` (`frontend.users.register.submit`)
- Controller: `src/Http/Controllers/Frontend/UserController.php`

## Validation Behavior

- If `email` is provided, it must pass Laravel `email` format validation.
- If `email` is not provided, the system generates a fallback email using a sanitized username and `request()->getHost()` (without port).
- Duplicate checks run against the final computed `username` and `email` values before user creation.

## Practical Example

- Input `username=john`, `email=abc` fails validation with `wncms::word.please_enter_a_valid_email`.
- Input `username=john`, empty `email` creates fallback email like `john@example.com`.

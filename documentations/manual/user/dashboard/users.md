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

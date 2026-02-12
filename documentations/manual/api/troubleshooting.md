# API Troubleshooting

Use this checklist when API requests fail.

## Quick Checklist

1. Confirm global API access is enabled: `enable_api_access`.
2. Confirm model-level API switch is enabled (example: `enable_api_post`, `enable_api_website`).
3. Confirm endpoint-level switch is enabled (example: `wncms_api_post_index`).
4. Verify `api_token` exists and belongs to an active user.
5. Verify request method and endpoint path are correct.
6. Check payload field names and data types.

## Common Cases

- `401`: Missing or invalid token.
- `403`: API switch is disabled.
- `404`: Wrong route path or wrong base domain.
- `422`: Validation failed.
- `500`: Server/runtime exception.

## Related Pages

- [Errors](./errors.md)
- [Authentication](./authentication.md)
- [Core Concepts](./core-concepts.md)

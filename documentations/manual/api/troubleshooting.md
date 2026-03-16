# API Troubleshooting

Use this checklist when API requests fail.

## Quick Checklist

1. Confirm global API access is enabled: `enable_api_access`.
2. Confirm model-level API switch is enabled (example: `enable_api_post`, `enable_api_website`).
3. Confirm endpoint-level switch is enabled (example: `wncms_api_post_index`).
4. Confirm the endpoint auth mode (`none`, `API Token`, or `Basic`) matches your request.
5. If `api_access_whitelist` is not empty, verify the request IP or `Origin`/`Referer` host is listed.
6. For `API Token` mode, verify `api_token` exists and belongs to an active user.
7. For `Basic` mode, verify the `Authorization: Basic ...` header contains a valid `email:password`.
8. Verify request method and endpoint path are correct.
9. Check payload field names and data types.

## Common Cases

- `401`: Missing or invalid API credentials.
- `403`: API switch is disabled or the whitelist check failed.
- `404`: Wrong route path or wrong base domain.
- `422`: Validation failed.
- `500`: Server/runtime exception.

## Related Pages

- [Errors](./errors.md)
- [Authentication](./authentication.md)
- [Core Concepts](./core-concepts.md)

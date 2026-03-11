---
name: wncms-api-testing
description: Verify WNCMS API behavior using the documented token, endpoint, and troubleshooting flow from the host project perspective.
---

## Goal
Verify that a host-project WNCMS API request is enabled, authenticated correctly, and using the right path and method.

## Hard Rules
- Use a real API token from an active user when testing authenticated endpoints.
- Verify the request path uses the correct `/api/v1/...` base.
- Verify the HTTP method matches the documented endpoint.
- Include `api_token` in the request body when the endpoint requires authentication.
- Report the HTTP status and key response message/data fields.

## Recommended Flow
1. Confirm global API access is enabled.
2. Confirm the model-level or endpoint-level API switch is enabled when relevant.
3. Send a simple request to the expected `/api/v1/...` path.
4. If the endpoint mutates data, repeat the request with a valid `api_token`.
5. Compare response status/message against the documented troubleshooting cases.

## Failure Checklist
- `401`: missing or invalid `api_token`
- `403`: global API or endpoint feature switch is disabled
- `404`: wrong route path or wrong base domain
- `422`: validation failed
- `500`: runtime exception

## Do Not Invent
- Do not assume a package-specific runtime path or server layout.
- If the endpoint behavior is unclear, read the API controller and manual docs before declaring the test result.

# Links API v2 Endpoints

Links are the guarded reference resource for the backend API v2. Reads and writes
share the stable automation envelope, and every write passes through
`LinkAutomationService`.

## Base URL and Authentication

Use `/api/v2/backend/links` with a backend v2 bearer token:

```bash
curl "https://your-domain.com/api/v2/backend/links?website_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

The token user is the mutation actor. An unauthenticated request returns `401`;
an actor without the route permission returns `403`.

## Routes and Permissions

| Method | URL | Route name | Permission |
| --- | --- | --- | --- |
| GET | `/api/v2/backend/links` | `api.v2.backend.links.index` | `link_index` |
| GET | `/api/v2/backend/links/{id-or-slug}` | `api.v2.backend.links.show` | `link_edit` |
| POST | `/api/v2/backend/links` | `api.v2.backend.links.store` | `link_create` |
| PATCH | `/api/v2/backend/links/{id-or-slug}` | `api.v2.backend.links.update` | `link_edit` |
| DELETE | `/api/v2/backend/links/{id-or-slug}` | `api.v2.backend.links.destroy` | `link_delete` |
| POST | `/api/v2/backend/links/bulk_update` | `api.v2.backend.links.bulk_update` | `link_edit` |
| POST | `/api/v2/backend/links/bulk_sync_tags` | `api.v2.backend.links.bulk_sync_tags` | `link_edit` |

Guarded bulk delete is not implemented. There is no
`api.v2.backend.links.bulk_delete` route, and clients must not call
`POST /api/v2/backend/links/bulk_delete`.

## Website Scope

Pass `website_id` to select a website explicitly. When omitted, WNCMS uses the
current request website. List, inspect, update, delete, and bulk target lookups
are limited to that website when Link website mode is `single` or `multi`.

- A missing current website context returns `409`.
- An unknown website ID returns a validation failure.
- A target outside the selected website is reported as not found.
- Forced writes also verify that the token actor may access the selected website.

## List Links

Supported query parameters:

| Parameter | Values | Default |
| --- | --- | --- |
| `status` | `active`, `inactive`, `all` | `active` |
| `keyword` | Link name text | none |
| `website_id` | Positive website ID | current website |
| `page` | Integer, minimum `1` | `1` |
| `per_page` | Integer from `1` to `100` | `20` |
| `sort` | `id`, `sort`, `name`, `clicks`, `created_at`, `updated_at` | `id` |
| `direction` | `asc`, `desc` | `desc` |

```bash
curl "https://your-domain.com/api/v2/backend/links?website_id=1&status=active&keyword=partner&sort=id&direction=asc&page=1&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

The list result contains normalized `items` and `pagination`.

## Inspect a Link

The `{id-or-slug}` path accepts either a numeric Link ID or its slug:

```bash
curl "https://your-domain.com/api/v2/backend/links/partner-site?website_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## Mutation Safety

Mutation requests preview by default and return `202` without changing Links,
tags, audits, or cache:

```json
{
  "website_id": 1,
  "name": "Partner Site",
  "url": "https://partner.example"
}
```

Add `"force": true` to execute a guarded write. `"dry_run": true` always wins
over `force` and keeps the request in preview mode.

Forced writes:

- use the authenticated token user as `actor_user_id`;
- enforce the configured Link permission and website scope;
- revalidate guarded bulk targets inside one transaction;
- run existing Link create/update hooks where defined;
- write successful changes to `mutation_audits` with `surface=api_v2`;
- flush the `links` cache after successful changes, but not for bulk no-ops.

Create returns `201` when written. Update, delete, bulk update, and bulk tag sync
return `200` when completed.

## Create, Update, and Delete

Create:

```bash
curl -X POST "https://your-domain.com/api/v2/backend/links" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "website_id": 1,
    "name": "Partner Site",
    "url": "https://partner.example",
    "slug": "partner-site",
    "link_categories": ["Partners"],
    "force": true
  }'
```

Update accepts a patch payload:

```bash
curl -X PATCH "https://your-domain.com/api/v2/backend/links/partner-site" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"website_id": 1, "sort": 20, "force": true}'
```

Delete:

```bash
curl -X DELETE "https://your-domain.com/api/v2/backend/links/partner-site" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"website_id": 1, "force": true}'
```

## Atomic Bulk Update

Each item contains an `identifier` plus `url`, `sort`, or both. The request
accepts at most 100 unique targets.

```bash
curl -X POST "https://your-domain.com/api/v2/backend/links/bulk_update" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "website_id": 1,
    "items": [
      {"identifier": 10, "sort": 20},
      {"identifier": "partner-site", "url": "https://new.example"}
    ],
    "force": true
  }'
```

All targets are validated before writing and revalidated in the transaction. A
missing, out-of-scope, invalid, or stale target prevents every item from being
written.

## Atomic Bulk Tag Synchronization

`action` is `sync`, `attach`, or `detach`. Supply at least one non-empty
`link_categories` or `link_tags` list. Omitted tag types are unchanged.

```bash
curl -X POST "https://your-domain.com/api/v2/backend/links/bulk_sync_tags" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "website_id": 1,
    "identifiers": [10, "partner-site"],
    "action": "sync",
    "link_categories": ["Partners"],
    "link_tags": ["Featured"],
    "force": true
  }'
```

The operation is atomic and writes one audit for each changed Link with a shared
run ID. No-op targets do not create audits.

## Response Envelope

Every Links API v2 response uses:

```json
{
  "code": 202,
  "status": "success",
  "message": "Link update dry-run plan generated.",
  "data": {
    "plan": {}
  },
  "meta": {
    "surface": "api_v2",
    "domain": "links",
    "action": "update",
    "dry_run": true,
    "force": false,
    "website_id": 1,
    "actor_user_id": 5
  },
  "errors": []
}
```

The HTTP status equals the envelope `code`.

## Related Pages

- [API Overview](../overview.md)
- [Authentication](../authentication.md)
- [API Routes](../../developer/route/api.md)

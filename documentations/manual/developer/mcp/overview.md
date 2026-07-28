# Local MCP Server

WNCMS includes the official Laravel MCP `^0.9` package as a production dependency. The bundled server is disabled by default, uses only the local standard-input/output transport, and currently exposes two read-only Link tools.

## Installation and enablement

Install or update WNCMS normally:

```bash
composer require secretwebmaster/wncms-core
```

Enable the server on a trusted machine:

```dotenv
WNCMS_MCP_ENABLED=true
```

Clear or rebuild the Laravel configuration cache after changing the environment:

```bash
php artisan config:clear
```

The local server handle defaults to `wncms`. Start it directly with:

```bash
php artisan mcp:start wncms
```

The server is not registered while `WNCMS_MCP_ENABLED` is false. WNCMS does not add a web MCP route, OAuth flow, or remote transport.

## Local client configuration

Configure an MCP client to launch Artisan from the host project. Replace both absolute paths for your machine:

```json
{
  "mcpServers": {
    "wncms": {
      "command": "/usr/bin/php",
      "args": [
        "/absolute/path/to/project/artisan",
        "mcp:start",
        "wncms"
      ],
      "env": {
        "WNCMS_MCP_ENABLED": "true"
      }
    }
  }
}
```

The client process working directory should be the Laravel host project, and the PHP executable must satisfy the WNCMS PHP requirement.

## Available tools

### `wncms-links-list`

Lists Links through `LinkAutomationService` without writing data.

| Input | Type | Required | Rules / default |
| --- | --- | --- | --- |
| `website_id` | integer | Yes | Existing website ID, minimum `1` |
| `status` | string | No | `active`, `inactive`, or `all`; default `active` |
| `keyword` | string | No | Link name keyword |
| `page` | integer | No | Minimum `1`; default `1` |
| `per_page` | integer | No | `1` to `100`; default `20` |
| `sort` | string | No | `id`, `sort`, `name`, `clicks`, `created_at`, or `updated_at`; default `id` |
| `direction` | string | No | `asc` or `desc`; default `desc` |

### `wncms-links-inspect`

Inspects one Link by numeric ID or slug through `LinkAutomationService` without writing data.

| Input | Type | Required | Rules |
| --- | --- | --- | --- |
| `identifier` | string or integer | Yes | Link ID or slug |
| `website_id` | integer | Yes | Existing website ID, minimum `1` |

Both tools are declared read-only, non-destructive, closed-world, and idempotent. No mutation MCP tools are registered.

## Structured response envelope

Every tool result uses the stable WNCMS automation envelope as MCP structured content:

```json
{
  "code": 200,
  "status": "success",
  "message": "Links listed.",
  "data": {
    "items": [],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 0,
      "last_page": 0
    }
  },
  "meta": {
    "surface": "mcp",
    "tool": "wncms-links-list",
    "domain": "links",
    "action": "list",
    "website_id": 1,
    "status": "active",
    "sort": "id",
    "direction": "desc"
  },
  "errors": []
}
```

Invalid inputs, including a missing or unknown `website_id`, return the same envelope with `code` `422` and `status` `fail`. An inspect miss returns `code` `404`, `status` `fail`, and the requested value in `errors.identifier`.

## Website scope and security

`website_id` is mandatory for both tools. The selected website must exist, and Link lookups pass the scope to `LinkAutomationService`, which applies the configured Link website mode so one website cannot inspect another website's bound Links.

The enabled local process is the trust boundary. These tools do not create an API token, remote actor, or separate WNCMS permission session; a local operator may select any existing website. Enable the server only on a trusted machine and only for clients that are allowed to read WNCMS Link data. The server has no web transport, and the tools do not change Links, tag pivots, website pivots, caches, or `mutation_audits`.

Mutation MCP remains intentionally out of scope until a separate actor, permission, confirmation, audit, and remote-transport design is approved.

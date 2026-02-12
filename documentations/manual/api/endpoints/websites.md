# Websites API

The Websites API lets you manage website models and website domains.

## Endpoints Overview

| Method   | Endpoint                           | Description                                 |
| -------- | ---------------------------------- | ------------------------------------------- |
| GET/POST | `/api/v1/websites`                 | List websites available to API user         |
| POST     | `/api/v1/websites/store`           | Create website (admin only)                 |
| GET/POST | `/api/v1/websites/{id}`            | Get single website                          |
| POST     | `/api/v1/websites/update/{id}`     | Update website                              |
| POST     | `/api/v1/websites/delete/{id}`     | Delete website (admin only)                 |
| POST     | `/api/v1/websites/add-domain`      | Add domain alias to a website               |
| POST     | `/api/v1/websites/remove-domain`   | Remove domain from website                  |

## Authentication

All endpoints require `api_token`.

## Feature Switches

Websites API requires both levels to be enabled:

- Model level: `enable_api_website`
- Endpoint level: matching action key (for example `wncms_api_website_update`)

## List Websites

### Endpoint

```
GET|POST /api/v1/websites
```

### Request Parameters

| Parameter    | Type   | Required | Description                                |
| ------------ | ------ | -------- | ------------------------------------------ |
| `api_token`  | string | Yes      | User API token                             |
| `keyword`    | string | No       | Filter by `domain` / `site_name`           |
| `page_size`  | int    | No       | Pagination size (default 20, max 100)      |

## Create Website

### Endpoint

```
POST /api/v1/websites/store
```

### Request Parameters

| Parameter    | Type   | Required | Description                      |
| ------------ | ------ | -------- | -------------------------------- |
| `api_token`  | string | Yes      | Admin API token                  |
| `site_name`  | string | Yes      | Website name                     |
| `domain`     | string | Yes      | Primary domain                   |
| `theme`      | string | No       | Theme key                        |
| `remark`     | string | No       | Remark                           |

## Get Single Website

### Endpoint

```
GET|POST /api/v1/websites/{id}
```

## Update Website

### Endpoint

```
POST /api/v1/websites/update/{id}
```

### Request Parameters

| Parameter                | Type    | Required | Description                            |
| ------------------------ | ------- | -------- | -------------------------------------- |
| `api_token`              | string  | Yes      | User API token                         |
| `user_id`                | integer | No       | Website owner user ID                  |
| `domain`                 | string  | No       | Primary domain                         |
| `site_name`              | string/object | No | Site name (supports translation map)   |
| `site_logo`              | string  | No       | Site logo path/url                     |
| `site_favicon`           | string  | No       | Site favicon path/url                  |
| `site_slogan`            | string/object | No | Site slogan (supports translation map) |
| `site_seo_keywords`      | string/object | No | SEO keywords (supports translation map) |
| `site_seo_description`   | string/object | No | SEO description (supports translation map) |
| `theme`                  | string  | No       | Theme key                              |
| `homepage`               | string  | No       | Homepage identifier                    |
| `remark`                 | string  | No       | Remark                                 |
| `meta_verification`      | string  | No       | Meta verification code                 |
| `head_code`              | string  | No       | HTML inserted into `<head>`            |
| `body_code`              | string  | No       | HTML inserted before `</body>`         |
| `analytics`              | string  | No       | Analytics script/config                |
| `license`                | string  | No       | License value                          |
| `enabled_page_cache`     | boolean | No       | Enable full page cache                 |
| `enabled_data_cache`     | boolean | No       | Enable data cache                      |

## Delete Website

### Endpoint

```
POST /api/v1/websites/delete/{id}
```

Admin only.

## Add Domain Alias

Add a domain alias (example: `demo001.wndhcms.com`) to a website.

### Endpoint

```
POST /api/v1/websites/add-domain
```

### Feature Toggle

This endpoint can be disabled via `enable_api_website_add_domain` setting.

### Request Parameters

| Parameter    | Type    | Required | Description                    |
| ------------ | ------- | -------- | ------------------------------ |
| `api_token`  | string  | Yes      | User API token                 |
| `website_id` | integer | Yes      | Target website ID              |
| `domain`     | string  | Yes      | Domain alias to add            |

### Response Example

```json
{
  "code": 200,
  "status": "success",
  "message": "Domain alias created",
  "data": {
    "website_id": 1,
    "domain": "demo001.wndhcms.com",
    "domain_alias_id": 8,
    "already_exists": false,
    "is_primary_domain": false
  },
  "extra": []
}
```

## Remove Domain

Remove a primary domain or alias from a website.

### Endpoint

```
POST /api/v1/websites/remove-domain
```

### Request Parameters

| Parameter    | Type    | Required | Description                      |
| ------------ | ------- | -------- | -------------------------------- |
| `api_token`  | string  | Yes      | User API token                   |
| `website_id` | integer | Yes      | Target website ID                |
| `domain`     | string  | Yes      | Domain to remove                 |

### Response Example - Remove Alias

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully Deleted",
  "data": {
    "website_id": 1,
    "removed_domain": "demo001.wndhcms.com",
    "new_primary_domain": "main-domain.com"
  },
  "extra": []
}
```

### Response Example - Cannot Remove Last Domain

```json
{
  "code": 422,
  "status": "fail",
  "message": "Cannot remove the last domain of a website",
  "data": [],
  "extra": []
}
```

## Behavior Notes

- Domain input is normalized to hostname only.
- Domains already used by another website (primary domain or alias) are rejected.
- Non-admin users can only add domains to websites they can access.
- Removing the primary domain is allowed only when another alias can be promoted.
- The API blocks removing the last remaining domain of a website.
- Cache tag `websites` is flushed after website/domain mutations.

## API Setting Keys

Website API actions are mapped to System Settings -> API toggles via model `$apiRoutes`:

- `wncms_api_website_index`
- `wncms_api_website_show`
- `wncms_api_website_store`
- `wncms_api_website_update`
- `wncms_api_website_delete`
- `wncms_api_website_add_domain`
- `wncms_api_website_remove_domain`

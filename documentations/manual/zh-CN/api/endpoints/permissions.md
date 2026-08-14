# 权限 API v2

权限是全局授权记录。与内容资源不同，这些端点不接受也不要求网站选择器。客户端应从 capabilities 读取 `website_scoped: false` 与 `website_scope_mode: none`，不应传入虚构的网站上下文。

| 方法 | 端点 | 权限 |
| --- | --- | --- |
| GET | `/api/v2/backend/permissions` | `permission_index` |
| GET | `/api/v2/backend/permissions/{id}` | `permission_show` |
| POST | `/api/v2/backend/permissions` | `permission_create` |
| PATCH | `/api/v2/backend/permissions/{id}` | `permission_edit` |
| DELETE | `/api/v2/backend/permissions/{id}` | `permission_delete` |
| POST | `/api/v2/backend/permissions/bulk_delete` | `permission_bulk_delete` |

列表支持 `keyword`、`page` 与 `per_page`，并返回 `roles_count`。创建与更新接受 `name` 及可选的 `guard_name`（默认 `web`）。敏感凭证变更遵守 capabilities 与 OpenAPI 公布的 step-up 和风险元数据。变更成功后会同时清除 Spatie 与 WNCMS 权限缓存。

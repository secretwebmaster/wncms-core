# 權限 API v2

權限是全域授權記錄。與內容資源不同，這些端點不接受也不要求網站選擇器。客戶端應從 capabilities 讀取 `website_scoped: false` 與 `website_scope_mode: none`，不應傳入虛構的網站上下文。

| 方法 | 端點 | 權限 |
| --- | --- | --- |
| GET | `/api/v2/backend/permissions` | `permission_index` |
| GET | `/api/v2/backend/permissions/{id}` | `permission_show` |
| POST | `/api/v2/backend/permissions` | `permission_create` |
| PATCH | `/api/v2/backend/permissions/{id}` | `permission_edit` |
| DELETE | `/api/v2/backend/permissions/{id}` | `permission_delete` |
| POST | `/api/v2/backend/permissions/bulk_delete` | `permission_bulk_delete` |

列表支援 `keyword`、`page` 與 `per_page`，並回傳 `roles_count`。建立與更新接受 `name` 及可選的 `guard_name`（預設 `web`）。敏感憑證異動遵守 capabilities 與 OpenAPI 公布的 step-up 和風險 metadata。異動成功後會同時清除 Spatie 與 WNCMS 權限快取。

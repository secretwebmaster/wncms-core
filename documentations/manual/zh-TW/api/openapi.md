# OpenAPI 3.1

`GET /api/v2/openapi.json` 回傳由目前安裝系統 API v2 registry 確定產生的
OpenAPI 文件。

## 存取與 Middleware

此文件不要求 token 或 website context，但仍要求全域 `enable_api_access`，
並經過 `api`、`api_v2_request_id`、`api_v2_whitelist`。Response body 是原始
OpenAPI JSON 文件，不是六 key API envelope；header 仍會包含 `X-Request-ID`。

```bash
curl "https://your-domain.com/api/v2/openapi.json" \
  -H "Accept: application/json"
```

## 文件契約

Root 會宣告：

- `openapi: 3.1.0`
- `jsonSchemaDialect: https://json-schema.org/draft/2020-12/schema`
- 已設定的 API title 與 version
- Registry 每個 operation 各一個 path/method entry
- 共用 bearer authentication 與 success/error envelope schema

Backend operation 宣告 `bearerAuth`；frontend operation 使用空的 security
requirement。Operation request 與 response schema 直接來自 runtime
capabilities 使用的同一個 registry。

## WNCMS Extensions

每個 operation 都會包含且只包含以下六個 WNCMS extension field：

| Extension | 含義 |
| --- | --- |
| `x-wncms-permission` | Actor 必須擁有的 WNCMS permission，或 `null` |
| `x-wncms-permission-mode` | `static` 或已驗證的 `model_template` permission 語義 |
| `x-wncms-ability` | 額外的 named ability，或 `null` |
| `x-wncms-website-scoped` | 是否要求目前 website context |
| `x-wncms-risk` | `read`、`write`、`destructive` 等風險分類 |
| `x-wncms-implementation` | `domain` 或 legacy implementation 分類 |

對於指定目標的通用 model operation，`x-wncms-permission` 會包含
`{model}_edit` 或 `{model}_bulk_delete` 等已驗證 template；runtime 只會使用
後台 resource catalog 中的 eligible model key 解析它。Consumer 必須讀取
`x-wncms-permission-mode`；含有 `{model}` 的 literal permission 會被拒絕，
不會被解釋成 template。
Registry 僅接受 `backend.models.update` 搭配 `{model}_edit`，以及
`backend.models.bulk_delete` 或 `backend.models.bulk_force_delete` 搭配
`{model}_bulk_delete` 使用此 mode。

```json
{
  "operationId": "backend.operations.cancel",
  "security": [{ "bearerAuth": [] }],
  "x-wncms-permission": "operation_cancel",
  "x-wncms-permission-mode": "static",
  "x-wncms-ability": null,
  "x-wncms-website-scoped": false,
  "x-wncms-risk": "destructive",
  "x-wncms-implementation": "domain"
}
```

`legacy_resource`、`legacy_controller`、`legacy_bridge` entry 會刻意保留在
OpenAPI 供相容性與探索使用，但不滿足最終 v7 domain parity。

## Snapshot 工作流程

Commit 內 snapshot 位於 `resources/api/openapi-v2.json`。請從 host application
root 產生或檢查：

```bash
php artisan wncms:api-v2-openapi --write=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
php artisan wncms:api-v2-openapi --check=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
```

若 snapshot 不同、無效或無法讀取，`--check` 會以非零狀態結束。契約驗證也會
拒絕重複 operation ID、重複 path/method、缺少 route、額外已註冊 route，
以及 registry/OpenAPI coverage drift。
## Authentication security

生成的 `2.1.0` 文件包含 Bearer、refresh Cookie、CSRF schemes、public-operation security、write-only request secrets 與 `x-wncms-*` metadata。Client 可由 `GET /api/v2/openapi.json` 生成，但目前 actor availability 應讀取 `GET /api/v2/capabilities`。

參閱[Contracts](./contracts.md)與[安全政策](./security-policy.md)。

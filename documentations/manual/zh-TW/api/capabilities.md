# Runtime Capabilities

`GET /api/v2/capabilities` 會報告目前安裝的 WNCMS 系統可向目前 actor
提供哪些 operation。Client 應使用此端點做 runtime feature discovery，
不要假設所有已設定或已寫入文件的 operation 都可使用。

## 請求

端點要求 `enable_api_access`、API whitelist gate，以及已驗證 session 或
personal access bearer token。外部 client 應傳入
`/api/v2/backend/auth/login` 回傳的 token。

```bash
curl "https://your-domain.com/api/v2/capabilities" \
  -H "Authorization: Bearer 1|your-plain-text-token" \
  -H "Accept: application/json"
```

此端點本身不要求目前 website，因為 client 需要靠 capabilities 診斷
website context 缺失。

## Permission 與 Website 過濾

Permission 過濾採用 fail-closed：

- 沒有 permission 的 operation 對已驗證 actor 可見。
- 有 permission 的 operation 若 `user->can(...)` 拒絕，會從回應中完全省略。
- 通用 model operation 會公開 `{model}_edit` 或 `{model}_bulk_delete` 等目標
  template。只有 actor 對後台 resource catalog 中至少一個 eligible model
  擁有相符 permission 時才會顯示。Eligible entry 的 configured class 必須是
  可實例化 Eloquent model，並宣告值完全相符的 public static `$modelKey`；無效
  entry 不會公開 operation。請求執行時，`model` selector 會先透過該 allowlist
  正規化，authorization 會把 exact resolved class 存入 server-side request
  attribute。Controller 不會再使用 namespace fallback，並會再次檢查 target
  key、action suffix 與具體 permission。任意 class name、未知 model key，以及
  模仿 trusted attribute 的 client body field 都會被拒絕。
- 已授權且 website-scoped 的 operation 在沒有目前 website 時仍然可見，
  但會標記 `available: false` 與
  `disabled_reasons: ["website.context_missing"]`。
- 存在 website context 時，該 operation 會是 `available: true` 且
  `disabled_reasons` 為空 list。

即使 permission 過濾後沒有 operation，domain 仍會保留；空的 `operations`
map 會序列化為 JSON object，而不是 array。

## 回應結構

標準六 key API v2 envelope 會在 `data.schema_version` 提供 schema 版本，
並以 `data.domains` object 依 domain key 分組。每個可見 operation 包含：

- `method`、`path`、`permission`、`permission_mode`、`ability`
- `website_scoped`、`risk`、`implementation`、`idempotent`
- `filters`、`sorts`、`includes`、`fields`
- `available`、`disabled_reasons`
- `request_schema`、`response_schema`

```json
{
  "code": 200,
  "status": "success",
  "message": "success",
  "data": {
    "schema_version": "2.0.0",
    "domains": {
      "links": {
        "key": "links",
        "label": "Links",
        "operations": {
          "backend.links.index": {
            "method": "GET",
            "path": "/api/v2/backend/links",
            "permission": "link_index",
            "ability": null,
            "website_scoped": true,
            "risk": "read",
            "implementation": "domain",
            "idempotent": false,
            "filters": [],
            "sorts": [],
            "includes": [],
            "fields": [],
            "available": true,
            "disabled_reasons": [],
            "request_schema": {
              "type": "object",
              "properties": {}
            },
            "response_schema": {
              "type": "object",
              "properties": {}
            }
          }
        }
      }
    }
  },
  "meta": {
    "request_id": "123e4567-e89b-42d3-a456-426614174000"
  },
  "errors": []
}
```

這些 schema 與 JSON Schema 2020-12 相容，也可能是 boolean schema。
透過已設定 contract provider 註冊的擴充會動態出現在同一回應中。

Literal WNCMS permission 的 `permission_mode` 是 `static`；只有已驗證的通用
model operation 使用 `model_template`。Consumer 必須讀取此 field，不得從
permission 文字推斷 mode。
Contract provider 僅可為 `backend.models.update` 搭配 `{model}_edit`，以及為
`backend.models.bulk_delete` 或 `backend.models.bulk_force_delete` 搭配
`{model}_bulk_delete` 使用 `model_template`；registry 會在發布前拒絕其他 mode、
operation 或 template 組合。

## Parity 判讀

製作完整 admin client 時請檢查 `implementation`。標記為
`legacy_resource`、`legacy_controller`、`legacy_bridge` 的 operation 雖然可用
且可探索，但仍屬於 migration inventory，不計入最終 v7 domain parity。
只有完成正式 `domain` 契約才能關閉該 parity 缺口。

Cancellation capability 只有在 actor 具有 `operation_cancel` 時才會顯示。
既有安裝的升級要求請參閱[非同步 Operations](./operations.md)。
## Authentication metadata

Schema `2.1.0` 新增 root `authentication` policy，以及每個 operation 的 `security_risk`、`accepted_credential_types`、`requires_step_up`、`step_up_purposes`、`action_plan_eligible`、`legacy_token_allowed`、`website_scope_mode`、`idempotency_required` 與 `refresh_transports`。Response 依 actor 與 credential 即時計算，並帶 `Cache-Control: private, no-store`。

參閱[安全政策](./security-policy.md)。

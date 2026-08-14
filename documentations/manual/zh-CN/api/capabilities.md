# Runtime Capabilities

`GET /api/v2/capabilities` 会报告目前安装的 WNCMS 系统可向当前 actor
提供哪些 operation。Client 应使用此端点做 runtime feature discovery，
不要假设所有已设定或已写入文件的 operation 都可使用。

## 请求

端点要求 `enable_api_access`、API whitelist gate，以及已验证 session 或
personal access bearer token。外部 client 应传入
`/api/v2/backend/auth/login` 返回的 token。

```bash
curl "https://your-domain.com/api/v2/capabilities" \
  -H "Authorization: Bearer 1|your-plain-text-token" \
  -H "Accept: application/json"
```

此端点本身不要求当前 website，因为 client 需要靠 capabilities 诊断
website context 缺失。

## Permission 与 Website 过滤

Permission 过滤采用 fail-closed：

- 没有 permission 的 operation 对已验证 actor 可见。
- 有 permission 的 operation 若 `user->can(...)` 拒绝，会从回应中完全省略。
- 通用 model operation 会公开 `{model}_edit` 或 `{model}_bulk_delete` 等目标
  template。只有 actor 对后台 resource catalog 中至少一个 eligible model
  拥有相符 permission 时才会显示。Eligible entry 的 configured class 必须是
  可实例化 Eloquent model，并声明值完全相符的 public static `$modelKey`；无效
  entry 不会公开 operation。请求执行时，`model` selector 会先通过该 allowlist
  规范化，authorization 会把 exact resolved class 存入 server-side request
  attribute。Controller 不会再使用 namespace fallback，并会再次检查 target
  key、action suffix 与具体 permission。任意 class name、未知 model key，以及
  模仿 trusted attribute 的 client body field 都会被拒绝。
- 已授权且 website-scoped 的 operation 在没有当前 website 时仍然可见，
  但会标记 `available: false` 与
  `disabled_reasons: ["website.context_missing"]`。
- 存在 website context 时，该 operation 会是 `available: true` 且
  `disabled_reasons` 为空 list。

即使 permission 过滤后没有 operation，domain 仍会保留；空的 `operations`
map 会序列化为 JSON object，而不是 array。

## 回应结构

标准六 key API v2 envelope 会在 `data.schema_version` 提供 schema 版本，
并以 `data.domains` object 按 domain key 分组。每个可见 operation 包含：

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

这些 schema 与 JSON Schema 2020-12 相容，也可能是 boolean schema。
透过已设定 contract provider 注册的扩充会动态出现在同一回应中。

Literal WNCMS permission 的 `permission_mode` 是 `static`；只有已验证的通用
model operation 使用 `model_template`。Consumer 必须读取此 field，不得从
permission 文字推断 mode。
Contract provider 仅可为 `backend.models.update` 搭配 `{model}_edit`，以及为
`backend.models.bulk_delete` 或 `backend.models.bulk_force_delete` 搭配
`{model}_bulk_delete` 使用 `model_template`；registry 会在发布前拒绝其他 mode、
operation 或 template 组合。

## Parity 判读

制作完整 admin client 时请检查 `implementation`。标记为
`legacy_resource`、`legacy_controller`、`legacy_bridge` 的 operation 虽然可用
且可探索，但仍属于 migration inventory，不计入最终 v7 domain parity。
只有完成正式 `domain` 契约才能关闭该 parity 缺口。

Cancellation capability 只有在 actor 具有 `operation_cancel` 时才会显示。
既有安装的升级要求请参阅[异步 Operations](./operations.md)。
## Authentication metadata

Schema `2.1.0` 新增 root `authentication` policy，以及每个 operation 的 `security_risk`、`accepted_credential_types`、`requires_step_up`、`step_up_purposes`、`action_plan_eligible`、`legacy_token_allowed`、`website_scope_mode`、`idempotency_required` 与 `refresh_transports`。Response 依 actor 与 credential 实时计算，并带 `Cache-Control: private, no-store`。

参阅[安全策略](./security-policy.md)。

# OpenAPI 3.1

`GET /api/v2/openapi.json` 返回由目前安装系统 API v2 registry 确定产生的
OpenAPI 文件。

## 存取与 Middleware

此文件不要求 token 或 website context，但仍要求全域 `enable_api_access`，
并经过 `api`、`api_v2_request_id`、`api_v2_whitelist`。Response body 是原始
OpenAPI JSON 文件，不是六 key API envelope；header 仍会包含 `X-Request-ID`。

```bash
curl "https://your-domain.com/api/v2/openapi.json" \
  -H "Accept: application/json"
```

## 文件契约

Root 会声明：

- `openapi: 3.1.0`
- `jsonSchemaDialect: https://json-schema.org/draft/2020-12/schema`
- 已设定的 API title 与 version
- Registry 每个 operation 各一个 path/method entry
- 共用 bearer authentication 与 success/error envelope schema

Backend operation 声明 `bearerAuth`；frontend operation 使用空的 security
requirement。Operation request 与 response schema 直接来自 runtime
capabilities 使用的同一个 registry。

## WNCMS Extensions

每个 operation 都会包含且只包含以下六个 WNCMS extension field：

| Extension | 含义 |
| --- | --- |
| `x-wncms-permission` | Actor 必须拥有的 WNCMS permission，或 `null` |
| `x-wncms-permission-mode` | `static` 或已验证的 `model_template` permission 语义 |
| `x-wncms-ability` | 额外的 named ability，或 `null` |
| `x-wncms-website-scoped` | 是否要求当前 website context |
| `x-wncms-risk` | `read`、`write`、`destructive` 等风险分类 |
| `x-wncms-implementation` | `domain` 或 legacy implementation 分类 |

对于指定目标的通用 model operation，`x-wncms-permission` 会包含
`{model}_edit` 或 `{model}_bulk_delete` 等已验证 template；runtime 只会使用
后台 resource catalog 中的 eligible model key 解析它。Consumer 必须读取
`x-wncms-permission-mode`；含有 `{model}` 的 literal permission 会被拒绝，
不会被解释成 template。
Registry 仅接受 `backend.models.update` 搭配 `{model}_edit`，以及
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

`legacy_resource`、`legacy_controller`、`legacy_bridge` entry 会刻意保留在
OpenAPI 供相容性与探索使用，但不满足最终 v7 domain parity。

## Snapshot 工作流程

Commit 内 snapshot 位于 `resources/api/openapi-v2.json`。请从 host application
root 产生或检查：

```bash
php artisan wncms:api-v2-openapi --write=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
php artisan wncms:api-v2-openapi --check=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
```

若 snapshot 不同、无效或无法读取，`--check` 会以非零状态结束。契约验证也会
拒绝重复 operation ID、重复 path/method、缺少 route、额外已注册 route，
以及 registry/OpenAPI coverage drift。
## Authentication security

生成的 `2.1.0` 文档包含 Bearer、refresh Cookie、CSRF schemes、public-operation security、write-only request secrets 与 `x-wncms-*` metadata。Client 可由 `GET /api/v2/openapi.json` 生成，但当前 actor availability 应读取 `GET /api/v2/capabilities`。

参阅[Contracts](./contracts.md)与[安全策略](./security-policy.md)。

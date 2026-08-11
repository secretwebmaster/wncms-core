# API v2 契约

API v2 Contract Kernel 是 WNCMS domain 与 operation 的机器可读真相来源。
Runtime capabilities、OpenAPI 文件与契约 parity 验证都由同一个
`ApiContractRegistry` 产生。

## 契约端点

| 方法 | 端点 | 身份验证 | 用途 |
| --- | --- | --- | --- |
| `GET` | `/api/v2/openapi.json` | 无 | 当前安装系统的 OpenAPI 3.1 文件 |
| `GET` | `/api/v2/capabilities` | 已验证 session 或 personal access bearer token | 当前 actor 可见的 operation |
| `GET` | `/api/v2/backend/operations/{id}` | 已验证 session 或 personal access bearer token | 读取自己拥有的异步 operation |
| `POST` | `/api/v2/backend/operations/{id}/cancel` | 身份验证、`operation_cancel` 与 `Idempotency-Key` | 取消自己拥有且可取消的 operation |

四个端点都要求全域 `enable_api_access` 设定，并依序经过 `api`、
`api_v2_request_id`、`api_v2_whitelist`，之后才执行端点专属身份验证。
启用 whitelist 时，请求 IP 或 `Origin`/`Referer` host 必须精确匹配。

## 稳定回应 Envelope

所有使用 envelope 的 API v2 回应都依固定顺序包含六个顶层 key：
`code`、`status`、`message`、`data`、`meta`、`errors`。

```json
{
  "code": 200,
  "status": "success",
  "message": "success",
  "data": {},
  "meta": {
    "request_id": "123e4567-e89b-42d3-a456-426614174000"
  },
  "errors": []
}
```

失败回应使用 `status: "fail"`，并在 `meta.error_code` 提供稳定的机器可读
错误码。`meta.request_id` 的 UUID 永远与回应 header `X-Request-ID` 相同。
请求若提供有效 UUID 会被保留；缺少或格式错误时会产生新的 UUID。

`GET /api/v2/openapi.json` 是刻意保留的例外：它直接返回 OpenAPI 文件，
不使用 envelope，但仍包含 `X-Request-ID`。当 `APP_DEBUG=false` 时，
非预期 exception 细节不会对外显示。

## Operation Metadata

每个 registry operation 都声明稳定 ID、domain、surface、HTTP method、path、
route name、permission、ability、website scope、risk、implementation 分类、
request/response JSON Schema、允许的 filters、sorts、includes、fields，
以及是否要求 idempotency。

Implementation 分类具有 parity 含义：

- `domain`：可计入最终 v7 parity 的正式 domain 实作。
- `legacy_resource`：旧版通用 resource controller。
- `legacy_controller`：旧版专用 API v2 controller。
- `legacy_bridge`：旧版 backend bridge action。

Legacy 分类会继续公开供 API client 探索及使用当前系统，但
`legacy_resource`、`legacy_controller`、`legacy_bridge` 不满足最终 v7 domain parity。

## List Query 契约

正式 list operation 可声明 `filter`、`sort`、`include`、`fields` allowlist。
通用 resolver 也接受 `page`、`per_page`、`keyword`、`direction`。
`page` 与 `per_page` 必须为正整数，`per_page` 上限为 `100`，`direction`
只能是 `asc` 或 `desc`。未声明的 filter、sort、include、field 会以
`validation.failed` 失败。

```text
?page=1&per_page=20&keyword=demo&filter[status]=active&sort=id&direction=desc&include=owner&fields=id,name
```

## 乐观并发控制

Domain mutation 可使用 Contract Kernel concurrency primitive 输出 `ETag`，
并要求客户端在 `If-Match` 回传相同 revision。Revision 包含 model class、
route key 与 `updated_at`。缺少或过期的 `If-Match` 会返回 HTTP `409` 与
`meta.error_code: "request.conflict"`。Weak 与 quoted ETag 语法都可接受。
此 primitive 提供给正式 domain migration 使用，不代表 legacy operation
已经强制套用。

## 契约验证

维护者可用以下命令侦测 route、registry 与 OpenAPI drift：

```bash
php artisan wncms:check-backend-api-v2-parity --contract --json
php artisan wncms:api-v2-openapi --check=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
```

另请参阅 [Runtime Capabilities](./capabilities.md)、[OpenAPI 3.1](./openapi.md)、
[异步 Operations](./operations.md) 与[错误参考](./errors.md)。

# API v2 契約

API v2 Contract Kernel 是 WNCMS domain 與 operation 的機器可讀真相來源。
Runtime capabilities、OpenAPI 文件與契約 parity 驗證都由同一個
`ApiContractRegistry` 產生。

## 契約端點

| 方法 | 端點 | 身份驗證 | 用途 |
| --- | --- | --- | --- |
| `GET` | `/api/v2/openapi.json` | 無 | 目前安裝系統的 OpenAPI 3.1 文件 |
| `GET` | `/api/v2/capabilities` | 已驗證 session 或 personal access bearer token | 目前 actor 可見的 operation |
| `GET` | `/api/v2/backend/operations/{id}` | 已驗證 session 或 personal access bearer token | 讀取自己擁有的非同步 operation |
| `POST` | `/api/v2/backend/operations/{id}/cancel` | 身份驗證、`operation_cancel` 與 `Idempotency-Key` | 取消自己擁有且可取消的 operation |

四個端點都要求全域 `enable_api_access` 設定，並依序經過 `api`、
`api_v2_request_id`、`api_v2_whitelist`，之後才執行端點專屬身份驗證。
啟用 whitelist 時，請求 IP 或 `Origin`/`Referer` host 必須精確符合。

## 穩定回應 Envelope

所有使用 envelope 的 API v2 回應都依固定順序包含六個頂層 key：
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

失敗回應使用 `status: "fail"`，並在 `meta.error_code` 提供穩定的機器可讀
錯誤碼。`meta.request_id` 的 UUID 永遠與回應 header `X-Request-ID` 相同。
請求若提供有效 UUID 會被保留；缺少或格式錯誤時會產生新的 UUID。

`GET /api/v2/openapi.json` 是刻意保留的例外：它直接回傳 OpenAPI 文件，
不使用 envelope，但仍包含 `X-Request-ID`。當 `APP_DEBUG=false` 時，
非預期 exception 細節不會對外顯示。

## Operation Metadata

每個 registry operation 都宣告穩定 ID、domain、surface、HTTP method、path、
route name、permission、ability、website scope、risk、implementation 分類、
request/response JSON Schema、允許的 filters、sorts、includes、fields，
以及是否要求 idempotency。

`surface` 嚴格限定為 `frontend` 或 `backend`，用於定義 transport 與身份驗證
邊界，不是 operation ID namespace。因此穩定的 domain ID 可保留在不同 transport
分類中（例如 `system.translations` 位於 `frontend` surface）。Query metadata
必須是唯一、非空、有效 UTF-8 的 ASCII identifier list，並可使用
`author.name` 這類 dotted segment。

Implementation 分類具有 parity 含義：

- `domain`：可計入最終 v7 parity 的正式 domain 實作。
- `legacy_resource`：舊版通用 resource controller。
- `legacy_controller`：舊版專用 API v2 controller。
- `legacy_bridge`：舊版 backend bridge action。

Legacy 分類會繼續公開供 API client 探索及使用目前系統，但
`legacy_resource`、`legacy_controller`、`legacy_bridge` 不滿足最終 v7 domain parity。

## List Query 契約

正式 list operation 可宣告 `filter`、`sort`、`include`、`fields` allowlist。
通用 resolver 也接受 `page`、`per_page`、`keyword`、`direction`。
`page` 與 `per_page` 必須為 PHP native integer 範圍內的正整數，`per_page` 上限為 `100`，`direction`
只能是 `asc` 或 `desc`。未宣告的 filter、sort、include、field 會以
`validation.failed` 失敗。

```text
?page=1&per_page=20&keyword=demo&filter[status]=active&sort=id&direction=desc&include=owner&fields=id,name
```

## 樂觀並行控制

Domain mutation 可使用 Contract Kernel concurrency primitive 輸出 `ETag`，
並要求 client 在 `If-Match` 回傳相同 revision。Revision 包含 model class、
route key 與 `updated_at`。缺少或過期的 `If-Match` 會回傳 HTTP `409` 與
`meta.error_code: "request.conflict"`。Weak 與 quoted ETag 語法都可接受。
此 primitive 提供給正式 domain migration 使用，不代表 legacy operation
已經強制套用。

## 契約驗證

維護者可用以下命令偵測 route、registry 與 OpenAPI drift：

```bash
php artisan wncms:check-backend-api-v2-parity --contract --json
php artisan wncms:api-v2-openapi --check=packages/secretwebmaster/wncms-core/resources/api/openapi-v2.json
```

另請參閱 [Runtime Capabilities](./capabilities.md)、[OpenAPI 3.1](./openapi.md)、
[非同步 Operations](./operations.md) 與[錯誤參考](./errors.md)。

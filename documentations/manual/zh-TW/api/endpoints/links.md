# Links API v2 端點

Links 是 backend API v2 的受保護參考資源。讀取與寫入共用穩定的
automation envelope，所有寫入都透過 `LinkAutomationService`。

## 基礎 URL 與身份驗證

使用 `/api/v2/backend/links` 與 backend v2 bearer token：

```bash
curl "https://your-domain.com/api/v2/backend/links?website_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

Token 使用者就是 mutation actor。未驗證請求回傳 `401`。隨後每條路由依序檢查
read/write ability、WNCMS permission 與明確的網站範圍。

## 路由與權限

| Method | URL | 路由名稱 | Ability | 權限 |
| --- | --- | --- | --- | --- |
| GET | `/api/v2/backend/links` | `api.v2.backend.links.index` | `links.read` | `link_index` |
| GET | `/api/v2/backend/links/{id-or-slug}` | `api.v2.backend.links.show` | `links.read` | `link_edit` |
| POST | `/api/v2/backend/links` | `api.v2.backend.links.store` | `links.write` | `link_create` |
| PATCH | `/api/v2/backend/links/{id-or-slug}` | `api.v2.backend.links.update` | `links.write` | `link_edit` |
| DELETE | `/api/v2/backend/links/{id-or-slug}` | `api.v2.backend.links.destroy` | `links.write` | `link_delete` |
| POST | `/api/v2/backend/links/bulk_update` | `api.v2.backend.links.bulk_update` | `links.write` | `link_edit` |
| POST | `/api/v2/backend/links/bulk_sync_tags` | `api.v2.backend.links.bulk_sync_tags` | `links.write` | `link_edit` |

受保護的批次刪除尚未實作，因此沒有 `api.v2.backend.links.bulk_delete`
路由，用戶端不得呼叫 `POST /api/v2/backend/links/bulk_delete`。

## 網站範圍

可透過 `website_id` 或規範的 `website_key=website:{id}` 明確選擇網站。Link 的
website mode 為 `single` 或 `multi` 時，列表、查看、更新、刪除與批次目標查詢都限制在該網站。

- 缺少 selector 或 selector 格式錯誤時回傳 `403 website.scope_missing`。
- 明確指定不存在或未授權的網站時回傳 `403 website.scope_denied`。
- 讀取會驗證 token 使用者能否存取所選網站。非管理員使用者必須在其 `websites`
  關係中擁有該網站；`admin` 與 `superadmin` 角色可選擇任一存在的網站。拒絕存取時回傳 `403`。
- 目標不屬於所選網站時依未找到處理。
- 強制寫入使用相同的網站存取策略。

## 列出 Links

| 參數 | 可用值 | 預設值 |
| --- | --- | --- |
| `status` | `active`、`inactive`、`all` | `active` |
| `keyword` | Link 名稱文字 | 無 |
| `website_id` | 正整數網站 ID | 使用 `website_key` 時可省略，否則必填 |
| `website_key` | 規範的 `website:{id}` key | 使用 `website_id` 時可省略，否則必填 |
| `page` | 最小為 `1` 的整數 | `1` |
| `per_page` | `1` 至 `100` | `20` |
| `sort` | `id`、`sort`、`name`、`clicks`、`created_at`、`updated_at` | `id` |
| `direction` | `asc`、`desc` | `desc` |

```bash
curl "https://your-domain.com/api/v2/backend/links?website_id=1&status=active&keyword=partner&sort=id&direction=asc&page=1&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

結果包含正規化的 `items` 與 `pagination`。

## 查看 Link

查看端點的 `{id-or-slug}` 可使用數字 ID 或 slug：

```bash
curl "https://your-domain.com/api/v2/backend/links/partner-site?website_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## Mutation 安全規則

Mutation 請求預設只預覽並回傳 `202`，不會變更 Link、標籤、audit 或快取。
加入 `"force": true` 才會執行受保護寫入；`"dry_run": true` 的優先順序永遠
高於 `force`。

強制寫入會：

- 使用已驗證 token 使用者作為 `actor_user_id`；
- 檢查 Link 權限與網站範圍；
- 在同一個 transaction 中重新驗證所有批次目標；
- 執行已定義的 Link create/update hooks；
- 以 `surface=api_v2` 寫入 `mutation_audits`；
- 有實際變更時刷新 `links` 快取，批次 no-op 時不刷新。

Create 寫入成功回傳 `201`；update、delete、bulk update 與 bulk tag sync
完成時回傳 `200`。

## Create、Update 與 Delete

```bash
curl -X POST "https://your-domain.com/api/v2/backend/links" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "website_id": 1,
    "name": "Partner Site",
    "url": "https://partner.example",
    "slug": "partner-site",
    "link_categories": ["Partners"],
    "force": true
  }'
```

Update 使用 patch payload；delete 也支援 ID 或 slug：

```bash
curl -X PATCH "https://your-domain.com/api/v2/backend/links/partner-site" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"website_id": 1, "sort": 20, "force": true}'
```

Delete：

```bash
curl -X DELETE "https://your-domain.com/api/v2/backend/links/partner-site" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"website_id": 1, "force": true}'
```

## 原子化 Bulk Update

每個 item 包含 `identifier`，以及 `url`、`sort` 或兩者。一次最多 100 個唯一目標。

```bash
curl -X POST "https://your-domain.com/api/v2/backend/links/bulk_update" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "website_id": 1,
  "items": [
    {"identifier": 10, "sort": 20},
    {"identifier": "partner-site", "url": "https://new.example"}
  ],
  "force": true
}'
```

寫入前與 transaction 內都會驗證全部目標。任何缺少、越界、無效或 stale
目標都會阻止所有 item 寫入。

## 原子化 Bulk Tag Sync

`action` 可為 `sync`、`attach` 或 `detach`。必須提供至少一個非空的
`link_categories` 或 `link_tags`；省略的 tag type 保持不變。

```bash
curl -X POST "https://your-domain.com/api/v2/backend/links/bulk_sync_tags" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "website_id": 1,
  "identifiers": [10, "partner-site"],
  "action": "sync",
  "link_categories": ["Partners"],
  "link_tags": ["Featured"],
  "force": true
}'
```

操作具有原子性，每個有變更的 Link 會使用同一個 run ID 寫入一筆 audit；
no-op 目標不會寫 audit。

JSON 請求中的 `identifiers`、`link_categories` 和 `link_tags` 必須編碼為 JSON
列表；物件與純量值會被拒絕並回傳 `422`。

## 回應 Envelope

```json
{
  "code": 202,
  "status": "success",
  "message": "Link update dry-run plan generated.",
  "data": {"plan": {}},
  "meta": {
    "surface": "api_v2",
    "domain": "links",
    "action": "update",
    "dry_run": true,
    "force": false,
    "website_id": 1,
    "actor_user_id": 5
  },
  "errors": []
}
```

HTTP status 與 envelope 的 `code` 相同。

## 相關頁面

- [API 總覽](../overview.md)
- [身份驗證](../authentication.md)
- [API 路由](../../developer/route/api.md)

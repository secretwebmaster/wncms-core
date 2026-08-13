# Links API v2 端点

Links 是 backend API v2 的受保护参考资源。读取与写入共用稳定的
automation envelope，所有写入都通过 `LinkAutomationService`。

## 基础 URL 与身份验证

使用 `/api/v2/backend/links` 与 backend v2 bearer token：

```bash
curl "https://your-domain.com/api/v2/backend/links?website_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

Token 使用者就是 mutation actor。未验证请求返回 `401`。随后每条路由依序检查
read/write ability、WNCMS permission 与明确的网站范围。

## 路由与权限

| Method | URL | 路由名称 | Ability | 权限 |
| --- | --- | --- | --- | --- |
| GET | `/api/v2/backend/links` | `api.v2.backend.links.index` | `links.read` | `link_index` |
| GET | `/api/v2/backend/links/{id-or-slug}` | `api.v2.backend.links.show` | `links.read` | `link_edit` |
| POST | `/api/v2/backend/links` | `api.v2.backend.links.store` | `links.write` | `link_create` |
| PATCH | `/api/v2/backend/links/{id-or-slug}` | `api.v2.backend.links.update` | `links.write` | `link_edit` |
| DELETE | `/api/v2/backend/links/{id-or-slug}` | `api.v2.backend.links.destroy` | `links.write` | `link_delete` |
| POST | `/api/v2/backend/links/bulk_update` | `api.v2.backend.links.bulk_update` | `links.write` | `link_edit` |
| POST | `/api/v2/backend/links/bulk_sync_tags` | `api.v2.backend.links.bulk_sync_tags` | `links.write` | `link_edit` |

受保护的批量删除尚未实现，因此没有 `api.v2.backend.links.bulk_delete`
路由，客户端不得调用 `POST /api/v2/backend/links/bulk_delete`。

## 网站范围

可通过 `website_id` 或规范的 `website_key=website:{id}` 明确选择网站。Link 的
website mode 为 `single` 或 `multi` 时，列表、查看、更新、删除与批量目标查询都限制在该网站。

- 缺少 selector 或 selector 格式错误时返回 `403 website.scope_missing`。
- 明确指定不存在或未授权的网站时返回 `403 website.scope_denied`。
- 读取会验证 token 使用者能否访问所选网站。非管理员使用者必须在其 `websites`
  关系中拥有该网站；`admin` 与 `superadmin` 角色可选择任一存在的网站。拒绝访问时返回 `403`。
- 目标不属于所选网站时按未找到处理。
- 强制写入使用相同的网站访问策略。

## 列出 Links

| 参数 | 可用值 | 默认值 |
| --- | --- | --- |
| `status` | `active`、`inactive`、`all` | `active` |
| `keyword` | Link 名称文字 | 无 |
| `website_id` | 正整数网站 ID | 使用 `website_key` 时可省略，否则必填 |
| `website_key` | 规范的 `website:{id}` key | 使用 `website_id` 时可省略，否则必填 |
| `page` | 最小为 `1` 的整数 | `1` |
| `per_page` | `1` 至 `100` | `20` |
| `sort` | `id`、`sort`、`name`、`clicks`、`created_at`、`updated_at` | `id` |
| `direction` | `asc`、`desc` | `desc` |

```bash
curl "https://your-domain.com/api/v2/backend/links?website_id=1&status=active&keyword=partner&sort=id&direction=asc&page=1&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

结果包含规范化的 `items` 与 `pagination`。

## 查看 Link

查看端点的 `{id-or-slug}` 可使用数字 ID 或 slug：

```bash
curl "https://your-domain.com/api/v2/backend/links/partner-site?website_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## Mutation 安全规则

Mutation 请求默认仅预览并返回 `202`，不会变更 Link、标签、audit 或缓存。
加入 `"force": true` 才会执行受保护写入；`"dry_run": true` 的优先级始终
高于 `force`。

强制写入会：

- 使用已验证 token 使用者作为 `actor_user_id`；
- 检查 Link 权限与网站范围；
- 在同一个 transaction 中重新验证所有批量目标；
- 执行已定义的 Link create/update hooks；
- 以 `surface=api_v2` 写入 `mutation_audits`；
- 有实际变更时刷新 `links` 缓存，批量 no-op 时不刷新。

Create 写入成功返回 `201`；update、delete、bulk update 与 bulk tag sync
完成时返回 `200`。

## Create、Update 与 Delete

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

Update 使用 patch payload；delete 也支持 ID 或 slug：

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

每个 item 包含 `identifier`，以及 `url`、`sort` 或两者。一次最多 100 个唯一目标。

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

写入前与 transaction 内都会验证全部目标。任何缺失、越界、无效或 stale
目标都会阻止所有 item 写入。

## 原子化 Bulk Tag Sync

`action` 可为 `sync`、`attach` 或 `detach`。必须提供至少一个非空的
`link_categories` 或 `link_tags`；省略的 tag type 保持不变。

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

操作具有原子性，每个有变更的 Link 会使用同一个 run ID 写入一笔 audit；
no-op 目标不会写 audit。

JSON 请求中的 `identifiers`、`link_categories` 和 `link_tags` 必须编码为 JSON
列表；对象与标量值会被拒绝并返回 `422`。

## 回应 Envelope

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

HTTP status 与 envelope 的 `code` 相同。

## 相关页面

- [API 总览](../overview.md)
- [身份验证](../authentication.md)
- [API 路由](../../developer/route/api.md)

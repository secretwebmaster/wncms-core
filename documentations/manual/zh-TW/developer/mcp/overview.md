# 本機 MCP Server

WNCMS 將官方 Laravel MCP `^0.9` 作為 production dependency。內建 server 預設關閉，僅使用本機標準輸入/輸出 transport，目前提供兩個唯讀 Link tools。

## 安裝與啟用

依一般方式安裝或更新 WNCMS：

```bash
composer require secretwebmaster/wncms-core
```

在受信任的機器上啟用 server：

```dotenv
WNCMS_MCP_ENABLED=true
```

變更環境設定後，清除或重建 Laravel config cache：

```bash
php artisan config:clear
```

本機 server handle 預設為 `wncms`。可直接啟動：

```bash
php artisan mcp:start wncms
```

當 `WNCMS_MCP_ENABLED` 為 false 時不會註冊 server。WNCMS 不會新增 web MCP route、OAuth flow 或 remote transport。

## 本機 client 設定

設定 MCP client 從 host project 啟動 Artisan。請將兩個絕對路徑替換為機器上的實際路徑：

```json
{
  "mcpServers": {
    "wncms": {
      "command": "/usr/bin/php",
      "args": [
        "/absolute/path/to/project/artisan",
        "mcp:start",
        "wncms"
      ],
      "env": {
        "WNCMS_MCP_ENABLED": "true"
      }
    }
  }
}
```

Client process 的 working directory 應為 Laravel host project，PHP executable 必須符合 WNCMS 的 PHP 版本要求。

## 可用 tools

### `wncms-links-list`

透過 `LinkAutomationService` 列出 Links，不寫入資料。

| Input | 類型 | 必填 | 規則 / 預設值 |
| --- | --- | --- | --- |
| `website_id` | integer | 是 | 已存在的 website ID，最小值 `1` |
| `status` | string | 否 | `active`、`inactive` 或 `all`；預設 `active` |
| `keyword` | string | 否 | Link 名稱關鍵字 |
| `page` | integer | 否 | 最小值 `1`；預設 `1` |
| `per_page` | integer | 否 | `1` 至 `100`；預設 `20` |
| `sort` | string | 否 | `id`、`sort`、`name`、`clicks`、`created_at` 或 `updated_at`；預設 `id` |
| `direction` | string | 否 | `asc` 或 `desc`；預設 `desc` |

### `wncms-links-inspect`

透過 `LinkAutomationService` 依數字 ID 或 slug 檢視單一 Link，不寫入資料。

| Input | 類型 | 必填 | 規則 |
| --- | --- | --- | --- |
| `identifier` | string 或 integer | 是 | Link ID 或 slug |
| `website_id` | integer | 是 | 已存在的 website ID，最小值 `1` |

兩個 tools 都宣告為 read-only、non-destructive、closed-world 與 idempotent。不會註冊任何 mutation MCP tools。

## Structured response envelope

每個 tool result 都以 MCP structured content 回傳穩定的 WNCMS automation envelope：

```json
{
  "code": 200,
  "status": "success",
  "message": "Links listed.",
  "data": {
    "items": [],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 0,
      "last_page": 0
    }
  },
  "meta": {
    "surface": "mcp",
    "tool": "wncms-links-list",
    "domain": "links",
    "action": "list",
    "website_id": 1,
    "status": "active",
    "sort": "id",
    "direction": "desc"
  },
  "errors": []
}
```

無效輸入（包括缺少或未知的 `website_id`）會以相同 envelope 回傳 `code` `422` 與 `status` `fail`。Inspect 找不到資料時回傳 `code` `404`、`status` `fail`，並在 `errors.identifier` 提供請求值。

## Website scope 與安全性

兩個 tools 都強制要求 `website_id`。所選 website 必須存在；Link lookup 會將 scope 交給 `LinkAutomationService`，並依已設定的 Link website mode 隔離資料，避免一個 website 檢視綁定到另一個 website 的 Links。

啟用後的本機 process 就是 trust boundary。這些 tools 不會建立 API token、remote actor 或獨立 WNCMS permission session；本機 operator 可選擇任何已存在的 website。僅應在受信任機器上啟用，並只交給獲准讀取 WNCMS Link 資料的 clients。Server 沒有 web transport；tools 不會修改 Links、tag pivots、website pivots、caches 或 `mutation_audits`。

在另外核准 actor、permission、confirmation、audit 與 remote-transport 設計前，mutation MCP 明確維持在 scope 之外。

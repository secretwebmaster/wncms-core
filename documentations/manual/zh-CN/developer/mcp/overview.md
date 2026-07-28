# 本机 MCP Server

WNCMS 将官方 Laravel MCP `^0.9` 作为 production dependency。内建 server 预设关闭，仅使用本机标准输入/输出 transport，目前提供两个唯读 Link tools。

## 安装与启用

依一般方式安装或更新 WNCMS：

```bash
composer require secretwebmaster/wncms-core
```

在受信任的机器上启用 server：

```dotenv
WNCMS_MCP_ENABLED=true
```

变更环境设定后，清除或重建 Laravel config cache：

```bash
php artisan config:clear
```

本机 server handle 预设为 `wncms`。可直接启动：

```bash
php artisan mcp:start wncms
```

当 `WNCMS_MCP_ENABLED` 为 false 时不会注册 server。WNCMS 不会新增 web MCP route、OAuth flow 或 remote transport。

## 本机 client 设定

设定 MCP client 从 host project 启动 Artisan。请将两个绝对路径替换为机器上的实际路径：

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

Client process 的 working directory 应为 Laravel host project，PHP executable 必须符合 WNCMS 的 PHP 版本要求。

## 可用 tools

### `wncms-links-list`

透过 `LinkAutomationService` 列出 Links，不写入资料。

| Input | 类型 | 必填 | 规则 / 预设值 |
| --- | --- | --- | --- |
| `website_id` | integer | 是 | 已存在的 website ID，最小值 `1` |
| `status` | string | 否 | `active`、`inactive` 或 `all`；预设 `active` |
| `keyword` | string | 否 | Link 名称关键字 |
| `page` | integer | 否 | 最小值 `1`；预设 `1` |
| `per_page` | integer | 否 | `1` 至 `100`；预设 `20` |
| `sort` | string | 否 | `id`、`sort`、`name`、`clicks`、`created_at` 或 `updated_at`；预设 `id` |
| `direction` | string | 否 | `asc` 或 `desc`；预设 `desc` |

### `wncms-links-inspect`

透过 `LinkAutomationService` 依数字 ID 或 slug 检视单一 Link，不写入资料。

| Input | 类型 | 必填 | 规则 |
| --- | --- | --- | --- |
| `identifier` | string 或 integer | 是 | Link ID 或 slug |
| `website_id` | integer | 是 | 已存在的 website ID，最小值 `1` |

两个 tools 都声明为 read-only、non-destructive、closed-world 与 idempotent。不会注册任何 mutation MCP tools。

## Structured response envelope

每个 tool result 都以 MCP structured content 回传稳定的 WNCMS automation envelope：

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

无效输入（包括缺少或未知的 `website_id`）会以相同 envelope 回传 `code` `422` 与 `status` `fail`。Inspect 找不到资料时回传 `code` `404`、`status` `fail`，并在 `errors.identifier` 提供请求值。

## Website scope 与安全性

两个 tools 都强制要求 `website_id`。所选 website 必须存在；Link lookup 会将 scope 交给 `LinkAutomationService`，并依已设定的 Link website mode 隔离资料，避免一个 website 检视绑定到另一个 website 的 Links。

启用后的本机 process 就是 trust boundary。这些 tools 不会建立 API token、remote actor 或独立 WNCMS permission session；本机 operator 可选择任何已存在的 website。仅应在受信任机器上启用，并只交给获准读取 WNCMS Link 资料的 clients。Server 没有 web transport；tools 不会修改 Links、tag pivots、website pivots、caches 或 `mutation_audits`。

在另外核准 actor、permission、confirmation、audit 与 remote-transport 设计前，mutation MCP 明确维持在 scope 之外。

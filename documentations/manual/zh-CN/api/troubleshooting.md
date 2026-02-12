# API 疑难排解

当 API 请求失败时，请先执行这份检查清单。

## 快速检查清单

1. 确认全域 API 开关已启用：`enable_api_access`。
2. 确认模型层 API 开关已启用（例如：`enable_api_post`、`enable_api_website`）。
3. 确认端点层开关已启用（例如：`wncms_api_post_index`）。
4. 确认 `api_token` 存在且属于有效用户。
5. 确认请求方法与端点路径正确。
6. 检查请求字段名称与数据类型。

## 常见状态

- `401`：缺少或无效 token。
- `403`：API 开关关闭。
- `404`：路径错误或网域错误。
- `422`：验证失败。
- `500`：服务器异常。

## 相关页面

- [错误码](./errors.md)
- [身份验证](./authentication.md)
- [核心概念](./core-concepts.md)

# 更新 API

更新 API 让您能够触发和监控 WNCMS 核心系统更新。

## 端点总览

| 方法 | 端点                      | 说明         |
| ---- | ------------------------- | ------------ |
| POST | `/api/v1/update`          | 触发系统更新 |
| POST | `/api/v1/update/progress` | 检查更新进度 |

## 触发更新

启动 WNCMS 核心或套件更新。

:::warning 管理操作
这是一个关键的系统操作，需要管理员存取权限，应谨慎使用。
:::

### 端点

```
POST /api/v1/update
```

### 身份验证

必需：是（透过 `api_token`）

### 功能开关

可以透过 `disable_core_update` 设定停用。

### 请求参数

| 参数        | 类型   | 必需 | 说明                                    |
| ----------- | ------ | ---- | --------------------------------------- |
| `api_token` | string | 是   | 管理员使用者 API token                  |
| `package`   | string | 是   | 要更新的套件名称（例如「wncms/wncms」） |
| `version`   | string | 否   | 特定版本（预设为最新）                  |

### 请求范例

```bash
curl -X POST "https://your-domain.com/api/v1/update" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "admin-api-token-here",
    "package": "wncms/wncms",
    "version": "6.1.0"
  }'
```

### 请求范例 - 最新版本

```bash
curl -X POST "https://your-domain.com/api/v1/update" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "admin-api-token-here",
    "package": "wncms/wncms"
  }'
```

### 回应范例 - 成功

```json
{
  "status": "success",
  "message": "Successfully updated",
  "version": "6.1.0"
}
```

### 回应范例 - 已在更新中

```json
{
  "status": "fail",
  "message": "Core update in progress"
}
```

### 回应范例 - 已停用

```json
{
  "status": "fail",
  "message": "Core update disabled"
}
```

## 更新运作方式

1. **锁定检查**：系统检查是否已有更新正在进行中
   - 锁定在 3 分钟无活动后过期
2. **设定更新状态**：将系统标记为「更新中」

   - 设定 `updating_core` 标志
   - 记录 `update_lock` 时间戳记

3. **执行更新**：呼叫 `wncms:update-package` Artisan 命令

   - 执行 Composer 更新
   - 应用资料库迁移
   - 发布资源
   - 清除快取

4. **释放锁定**：无论成功或失败都清除更新状态

## 检查更新进度

监控正在进行的更新的状态。

### 端点

```
POST /api/v1/update/progress
```

### 身份验证

必需：可透过设定配置

### 请求参数

| 参数        | 类型   | 必需 | 说明                                 |
| ----------- | ------ | ---- | ------------------------------------ |
| `api_token` | string | 是\* | 使用者 API token                     |
| `itemId`    | string | 是   | 要检查的项目（核心更新使用「core」） |

\*如果启用了身份验证则为必需

### 请求范例

```bash
curl -X POST "https://your-domain.com/api/v1/update/progress" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "itemId": "core"
  }'
```

### 回应范例 - 更新进行中

```json
{
  "status": "success",
  "message": "Successfully fetched updating progress",
  "progress": 1
}
```

### 回应范例 - 无更新执行中

```json
{
  "status": "success",
  "message": "Successfully fetched updating progress",
  "progress": 0
}
```

### 进度值

| 值  | 状态   | 说明               |
| --- | ------ | ------------------ |
| `0` | 空闲   | 无更新进行中       |
| `1` | 更新中 | 更新目前正在执行中 |

## 最佳实务

### 1. 更新前备份

在触发更新之前始终备份您的资料库和档案：

```bash
# 在 API 呼叫之前
php artisan backup:run
```

### 2. 定期检查进度

不要假设更新会立即完成：

```javascript
async function waitForUpdate() {
  let attempts = 0
  const maxAttempts = 60 // 最多 5 分钟

  while (attempts < maxAttempts) {
    const progress = await checkProgress()
    if (progress.progress === 0) {
      return true
    }

    await sleep(5000)
    attempts++
  }

  throw new Error('Update timeout')
}
```

### 3. 优雅地处理失败

```javascript
try {
  const result = await triggerUpdate(params)

  if (result.status === 'fail') {
    // 记录错误，通知管理员
    await notifyAdmin(result.message)
  }
} catch (error) {
  // 处理网路错误
  await rollbackUpdate()
}
```

## 疑难排解

有关更多协助，请参阅[疑难排解指南](../troubleshooting.md)。

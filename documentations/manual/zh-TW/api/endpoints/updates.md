# 更新 API

更新 API 讓您能夠觸發和監控 WNCMS 核心系統更新。

## 端點總覽

| 方法 | 端點                      | 說明         |
| ---- | ------------------------- | ------------ |
| POST | `/api/v1/update`          | 觸發系統更新 |
| POST | `/api/v1/update/progress` | 檢查更新進度 |

## 觸發更新

啟動 WNCMS 核心或套件更新。

:::warning 管理操作
這是一個關鍵的系統操作，需要管理員存取權限，應謹慎使用。
:::

### 端點

```
POST /api/v1/update
```

### 身份驗證

必需：是（透過 `api_token`）

### 功能開關

可以透過 `disable_core_update` 設定停用。

### 請求參數

| 參數        | 類型   | 必需 | 說明                                    |
| ----------- | ------ | ---- | --------------------------------------- |
| `api_token` | string | 是   | 管理員使用者 API token                  |
| `package`   | string | 是   | 要更新的套件名稱（例如「wncms/wncms」） |
| `version`   | string | 否   | 特定版本（預設為最新）                  |

### 請求範例

```bash
curl -X POST "https://your-domain.com/api/v1/update" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "admin-api-token-here",
    "package": "wncms/wncms",
    "version": "6.1.0"
  }'
```

### 請求範例 - 最新版本

```bash
curl -X POST "https://your-domain.com/api/v1/update" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "admin-api-token-here",
    "package": "wncms/wncms"
  }'
```

### 回應範例 - 成功

```json
{
  "status": "success",
  "message": "Successfully updated",
  "version": "6.1.0"
}
```

### 回應範例 - 已在更新中

```json
{
  "status": "fail",
  "message": "Core update in progress"
}
```

### 回應範例 - 已停用

```json
{
  "status": "fail",
  "message": "Core update disabled"
}
```

## 更新運作方式

1. **鎖定檢查**：系統檢查是否已有更新正在進行中
   - 鎖定在 3 分鐘無活動後過期
2. **設定更新狀態**：將系統標記為「更新中」

   - 設定 `updating_core` 標誌
   - 記錄 `update_lock` 時間戳記

3. **執行更新**：呼叫 `wncms:update-package` Artisan 命令

   - 執行 Composer 更新
   - 應用資料庫遷移
   - 發布資源
   - 清除快取

4. **釋放鎖定**：無論成功或失敗都清除更新狀態

## 檢查更新進度

監控正在進行的更新的狀態。

### 端點

```
POST /api/v1/update/progress
```

### 身份驗證

必需：可透過設定配置

### 請求參數

| 參數        | 類型   | 必需 | 說明                                 |
| ----------- | ------ | ---- | ------------------------------------ |
| `api_token` | string | 是\* | 使用者 API token                     |
| `itemId`    | string | 是   | 要檢查的項目（核心更新使用「core」） |

\*如果啟用了身份驗證則為必需

### 請求範例

```bash
curl -X POST "https://your-domain.com/api/v1/update/progress" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "itemId": "core"
  }'
```

### 回應範例 - 更新進行中

```json
{
  "status": "success",
  "message": "Successfully fetched updating progress",
  "progress": 1
}
```

### 回應範例 - 無更新執行中

```json
{
  "status": "success",
  "message": "Successfully fetched updating progress",
  "progress": 0
}
```

### 進度值

| 值  | 狀態   | 說明               |
| --- | ------ | ------------------ |
| `0` | 空閒   | 無更新進行中       |
| `1` | 更新中 | 更新目前正在執行中 |

## 最佳實務

### 1. 更新前備份

在觸發更新之前始終備份您的資料庫和檔案：

```bash
# 在 API 呼叫之前
php artisan backup:run
```

### 2. 定期檢查進度

不要假設更新會立即完成：

```javascript
async function waitForUpdate() {
  let attempts = 0
  const maxAttempts = 60 // 最多 5 分鐘

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

### 3. 優雅地處理失敗

```javascript
try {
  const result = await triggerUpdate(params)

  if (result.status === 'fail') {
    // 記錄錯誤，通知管理員
    await notifyAdmin(result.message)
  }
} catch (error) {
  // 處理網路錯誤
  await rollbackUpdate()
}
```

## 疑難排解

有關更多協助，請參閱[疑難排解指南](../troubleshooting.md)。

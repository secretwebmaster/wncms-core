# 開發命令總覽

本頁說明 WNCMS 常用開發腳手架命令。

## `wncms:create-model`

在宿主專案中建立模型腳手架（模型、遷移、後台控制器、starter 視圖、權限）。

```bash
php artisan wncms:create-model Novel
```

行為摘要：
- 不存在時建立 `app/Models/Novel.php`。
- 建立 `novels` 資料表遷移檔。
- 建立 `app/Http/Controllers/Backend/NovelController.php`。
- 呼叫 `wncms:create-model-view novel`。
- 呼叫 `wncms:create-model-permission novel`。
- 可選擇將路由附加到 `routes/custom_backend.php`。

## `wncms:create-model-view`

使用 starter 模板為模型建立後台 blade 檔案。

```bash
php artisan wncms:create-model-view novel
```

產生檔案：
- `resources/views/backend/novels/index.blade.php`
- `resources/views/backend/novels/create.blade.php`
- `resources/views/backend/novels/edit.blade.php`
- `resources/views/backend/novels/form-items.blade.php`

Starter 模板路徑解析順序：
1. 套件根目錄 `resources/views/backend/starters`
2. 套件根目錄上一層 `../resources/views/backend/starters`
3. 內部備援路徑：`src/../../resources/views/backend/starters`

若找不到有效 starter 路徑，命令會以失敗結束並列出所有已檢查路徑。

## `wncms:create-model-permission`

為模型 key 建立常用後台權限。

```bash
php artisan wncms:create-model-permission novel
```

常見權限後綴包含：
- `_index`
- `_create`
- `_clone`
- `_edit`
- `_delete`
- `_bulk_delete`

## 疑難排解

- `Source view file not found`：
  檢查套件中的 `resources/views/backend/starters` 是否有 starter blade 檔案。
- 命令未建立視圖：
  確認 `resources/views/backend/{plural}/` 下目標檔案不是已存在狀態。
- 路由權限被拒絕：
  重新執行 `wncms:create-model-permission {model}`，並在後台確認角色已指派對應權限。

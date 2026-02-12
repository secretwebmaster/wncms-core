# Commit Style Guide

此專案遵循 **Conventional Commits** 標準。

## 格式

```
<type>: <short description>
```

- 全部小寫
- 結尾不加句號
- 簡短且清楚

## 常見類型

- **feat** – 新功能
- **fix** – bug 修復
- **update** – 小更新或增強
- **improve** – 一般改進，不包含新功能
- **refactor** – 程式碼重構
- **style** – 格式化或僅 UI 變更
- **docs** – 文件變更
- **chore** – 維護任務
- **perf** – 效能改進
- **test** – 與測試相關

## 範例

```
fix: resolve smtp dsn error when mail config is empty
feat: add gallery field to page templates
update: improve frontend pagination styles
refactor: unify model manager logic
docs: add tag filter usage examples
```

## 規則

- 保持訊息簡短且具描述性。
- 使用正確的類型使歷史記錄易於閱讀。
- 每個 commit 僅包含一個邏輯變更。
- 僅使用英文。

# Commit Style Guide

此专案遵循 **Conventional Commits** 标准。

## 格式

```
<type>: <short description>
```

- 全部小写
- 结尾不加句号
- 简短且清楚

## 常见类型

- **feat** – 新功能
- **fix** – bug 修复
- **update** – 小更新或增强
- **improve** – 一般改进，不包含新功能
- **refactor** – 程式码重构
- **style** – 格式化或仅 UI 变更
- **docs** – 文件变更
- **chore** – 维护任务
- **perf** – 效能改进
- **test** – 与测试相关

## 范例

```
fix: resolve smtp dsn error when mail config is empty
feat: add gallery field to page templates
update: improve frontend pagination styles
refactor: unify model manager logic
docs: add tag filter usage examples
```

## 规则

- 保持讯息简短且具描述性。
- 使用正确的类型使历史记录易于阅读。
- 每个 commit 仅包含一个逻辑变更。
- 仅使用英文。

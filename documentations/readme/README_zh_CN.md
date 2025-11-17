# Wncms 核心包

此仓库为 **WNCMS 的核心包 (wncms-core)**，提供整个 WNCMS 系统运行所需的基础结构，包括核心类、管理器、辅助函数、Service Provider、后台架构、数据库迁移等。

此核心包 **不是一个独立 CMS**，它是完整 WNCMS 平台的底层框架。

关于完整的 WNCMS 平台介绍、安装方式、主题系统、插件系统等内容，请参考主项目的 README.md。

## 功能特点

- 统一的 ModelManager 架构
- 翻译、标签、缓存与配置等核心服务
- 后台控制器、模板及开发脚手架
- 主题加载、验证与参数支持
- 插件加载机制
- API 辅助工具与 Resource Transformer
- 安装流程与环境初始化工具
- 可覆盖模型与管理器的可扩展架构设计

## 文档

所有文档位于：

- `documentations/readme/` ─ 多语言 README
- `documentations/change/` ─ 更新日志
- `documentations/manual/` ─ 技术手册

## Demo

演示站点：https://demo.wncms.cc

该 Demo 展示完整 WNCMS 系统，由本核心包驱动。

## License

MIT License

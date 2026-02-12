---
layout: home
layoutClass: m-home-layout

hero:
  name: WNCMS
  text: 模组化 Laravel CMS
  tagline: 使用统一的 Laravel 生态系建立网站、套件与 API。
  image:
    src: /favicon.png
    alt: WNCMS 图示
  actions:
    - text: 入门指南
      link: /zh-CN/getting-started/overview
    - text: 开发者指南
      link: /zh-CN/developer/overview
    - text: 使用者指南
      link: /zh-CN/user/overview
      theme: alt
    - text: API 文件
      link: /zh-CN/api/overview
      theme: alt

features:
  - icon: ⚙️
    title: 模组化架构
    details: WNCMS 采用 Composer 套件化设计。透过 Laravel 服务提供者与独立套件，轻松扩充或建立新功能。
    link: /zh-CN/developer/overview
    linkText: 开发者指南

  - icon: 🧩
    title: 套件开发
    details: 建立可重复使用的套件并发布至 Packagist。建立模型、控制器、路由与迁移档，无缝整合至 WNCMS 核心。
    link: /zh-CN/package/overview
    linkText: 套件开发

  - icon: 🎨
    title: 主题系统
    details: 在专案的 <code>public/themes/</code> 目录下建立前台主题。轻松管理版面配置、设定档、选单与多语系翻译。
    link: /zh-CN/developer/theme/theme-structure
    linkText: 主题结构

  - icon: 🌍
    title: 多语系与多网站
    details: 内建 Trait 提供完整的多语系与多网站支援。单一 WNCMS 安装即可管理多个网站与语言版本。
    link: /zh-CN/developer/locale/localization-overview
    linkText: 多语系总览

  - icon: 🔗
    title: RESTful API
    details: 符合业界标准的 REST API，支援文章、页面、选单与标签管理。使用 API Token 验证，轻松整合 React、Vue、Next.js 等框架。
    link: /zh-CN/api/overview
    linkText: API 文件

  - icon: 🛠️
    title: 开发者友善工具
    details: 强大的管理器、基础控制器与辅助函数。内建快取机制、CRUD 操作、档案管理与完整技术文件。
    link: /zh-CN/developer/overview
    linkText: 开发者工具
---

<style>
.m-home-layout .image-src:hover {
  transform: translate(-50%, -50%) rotate(666turn);
  transition: transform 59s 1s cubic-bezier(0.3, 0, 0.8, 1);
}

.m-home-layout .details small {
  opacity: 0.8;
}

.m-home-layout .bottom-small {
  display: block;
  margin-top: 2em;
  text-align: right;
}
</style>

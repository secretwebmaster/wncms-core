---
layout: home
layoutClass: m-home-layout

hero:
  name: WNCMS
  text: 模組化 Laravel CMS
  tagline: 使用統一的 Laravel 生態系建立網站、套件與 API。
  image:
    src: /favicon.png
    alt: WNCMS 圖示
  actions:
    - text: 入門指南
      link: /zh-TW/getting-started/overview
    - text: 開發者指南
      link: /zh-TW/developer/overview
    - text: 使用者指南
      link: /zh-TW/user/overview
      theme: alt
    - text: API 文件
      link: /zh-TW/api/overview
      theme: alt

features:
  - icon: ⚙️
    title: 模組化架構
    details: WNCMS 採用 Composer 套件化設計。透過 Laravel 服務提供者與獨立套件，輕鬆擴充或建立新功能。
    link: /zh-TW/developer/overview
    linkText: 開發者指南

  - icon: 🧩
    title: 套件開發
    details: 建立可重複使用的套件並發布至 Packagist。建立模型、控制器、路由與遷移檔，無縫整合至 WNCMS 核心。
    link: /zh-TW/package/overview
    linkText: 套件開發

  - icon: 🎨
    title: 主題系統
    details: 在專案的 <code>public/themes/</code> 目錄下建立前台主題。輕鬆管理版面配置、設定檔、選單與多語系翻譯。
    link: /zh-TW/developer/theme/theme-structure
    linkText: 主題結構

  - icon: 🌍
    title: 多語系與多網站
    details: 內建 Trait 提供完整的多語系與多網站支援。單一 WNCMS 安裝即可管理多個網站與語言版本。
    link: /zh-TW/developer/locale/localization-overview
    linkText: 多語系總覽

  - icon: 🔗
    title: RESTful API
    details: 符合業界標準的 REST API，支援文章、頁面、選單與標籤管理。使用 API Token 驗證，輕鬆整合 React、Vue、Next.js 等框架。
    link: /zh-TW/api/overview
    linkText: API 文件

  - icon: 🛠️
    title: 開發者友善工具
    details: 強大的管理器、基礎控制器與輔助函數。內建快取機制、CRUD 操作、檔案管理與完整技術文件。
    link: /zh-TW/developer/overview
    linkText: 開發者工具
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

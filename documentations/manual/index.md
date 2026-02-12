---
layout: home
layoutClass: 'm-home-layout'

hero:
  name: WNCMS
  text: Modular Laravel CMS
  tagline: Build websites, packages, and APIs with a unified Laravel ecosystem.
  image:
    src: /favicon.png
    alt: WNCMS Logo
  actions:
    - text: Get Started
      link: /getting-started/overview
    - text: Developer Guide
      link: /developer/overview
    - text: User Guide
      link: /user/overview
      theme: alt
    - text: API Reference
      link: /api/overview
      theme: alt

features:
  - icon: ⚙️
    title: Modular Architecture
    details: WNCMS is built around modular Composer packages. Extend or create new functionality with clean Laravel service providers and independent packages.
    link: /developer/overview
    linkText: Developer Guide

  - icon: 🧩
    title: Package Development
    details: Build and publish reusable packages on Packagist. Create models, controllers, routes, and migrations that integrate seamlessly with WNCMS Core.
    link: /package/overview
    linkText: Package Development

  - icon: 🎨
    title: Theme System
    details: Create frontend themes in your project's <code>public/themes/</code> directory. Manage layouts, configurations, menus, and translations with ease.
    link: /developer/theme/theme-structure
    linkText: Theme Structure

  - icon: 🌍
    title: Multi-language & Multi-site
    details: Full localization and multi-website support with built-in traits. Manage multiple sites and languages from a single WNCMS installation.
    link: /developer/locale/localization-overview
    linkText: Localization Overview

  - icon: 🔗
    title: RESTful API
    details: Industry-standard REST API for posts, pages, menus, and tags. Authenticate with API tokens and integrate with React, Vue, Next.js, and more.
    link: /api/overview
    linkText: API Documentation

  - icon: 🛠️
    title: Developer-Friendly Tools
    details: Powerful managers, base controllers, and helper functions. Built-in caching, CRUD operations, file management, and extensive documentation.
    link: /developer/overview
    linkText: Developer Tools
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

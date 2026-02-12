# WNCMS Naming Standard

This document defines the official naming conventions for all repositories, packages, plugins, themes, and projects within the WNCMS ecosystem.
The goal is to ensure clarity, consistency, and long-term maintainability.

## 1. Core System (Composer Packages)

These are foundational libraries containing models, traits, service providers, and system functionality.

Format:

```
secretwebmaster/wncms-{package}
```

Examples:

```
secretwebmaster/wncms-core
secretwebmaster/wncms-novels
secretwebmaster/wncms-faqs
secretwebmaster/wncms-tags
```

Characteristics:

- Must not contain full Laravel application structure
- Loaded via Composer
- Provides reusable WNCMS components

## 2. WNCMS Base Application

This is the full Laravel project skeleton that loads `wncms-core`.

Official repo:

```
secretwebmaster/wncms
```

Characteristics:

- Contains full Laravel directory structure
- Acts as the base app for new WNCMS installations
- Does not include site-specific customizations

## 3. WNCMS Projects (Custom Applications)

Projects are complete websites or applications built on top of WNCMS.

Format:

```
secretwebmaster/wncms-project-{project_name}
```

Examples:

```
secretwebmaster/wncms-project-list
secretwebmaster/wncms-project-video
secretwebmaster/wncms-project-navigation
```

Characteristics:

- Full Laravel application
- Extends or customizes the base WNCMS installation
- May include custom modules, configs, themes, and views

This naming avoids confusion with Composer packages and clearly identifies them as standalone applications.

## 4. WNCMS Plugins

Plugins extend core features via the plugin system.

Format:

```
secretwebmaster/wncms-plugin-{plugin_name}
```

Examples:

```
secretwebmaster/wncms-plugin-keyword-replacer
secretwebmaster/wncms-plugin-affiliate
secretwebmaster/wncms-plugin-analytics
```

Characteristics:

- Must follow the WNCMS plugin structure
- Provides isolated, activatable features
- Not a full Laravel project

## 5. WNCMS Themes

Themes provide frontend presentation layers for WNCMS.

Format:

```
secretwebmaster/wncms-theme-{theme_name}
```

Examples:

```
secretwebmaster/wncms-theme-starter
secretwebmaster/wncms-theme-novelist
secretwebmaster/wncms-theme-custom
```

Characteristics:

- Stored under the WNCMS theme directory
- Should include config, assets, and views
- Should not include backend logic or models

## 6. Summary Table

| Category     | Format               | Example                       |
| ------------ | -------------------- | ----------------------------- |
| Core package | wncms-{package}      | wncms-core                    |
| Base app     | wncms                | wncms                         |
| Projects     | wncms-project-{name} | wncms-project-video           |
| Plugins      | wncms-plugin-{name}  | wncms-plugin-keyword-replacer |
| Themes       | wncms-theme-{name}   | wncms-theme-starter           |

## 7. Goals of This Standard

- Prevent naming conflicts
- Clearly separate packages vs. projects vs. plugins vs. themes
- Make repository purpose obvious at a glance
- Maintain long-term consistency across all WNCMS official and third-party packages

# Developer Guide Overview

WNCMS is designed to help Laravel developers extend functionality through models, controllers, managers, and packages.  
This section provides a detailed reference for how to build new features and customize existing ones while maintaining full compatibility with the WNCMS core.

## Developer Types

There are two kinds of developers working with WNCMS:

### Website Developer

You install WNCMS for a client and extend the system inside a Laravel application.  
You may:

- Create new **models**, **controllers**, and **managers** inside `app/`.
- Extend or override classes provided by `wncms-core`.
- Add custom routes, migrations, or Blade templates.
- Integrate existing WNCMS components like `PostManager`, `LinkManager`, and translation traits.

### Package Developer

You create **Composer packages** that extend WNCMS functionality and can be installed by others.  
You may:

- Register packages via `wncms()->registerPackage()`.
- Provide migrations, seeders, translations, and backend menus.
- Build independent modules like `wncms-faqs`, `wncms-ecommerce`, etc.

## Core Extension Layers

WNCMS provides base classes and traits to make development consistent:

| Layer      | Description                                       | Example Base Class                                 |
| ---------- | ------------------------------------------------- | -------------------------------------------------- |
| Model      | Data representation and Eloquent integration      | `Wncms\Models\BaseModel`                           |
| Controller | Routing logic for backend, frontend, and API      | `Wncms\Http\Controllers\Backend\BackendController` |
| Manager    | Data access and business logic abstraction        | `Wncms\Services\Managers\ModelManager`             |
| Resource   | API serialization layer                           | `Wncms\Http\Resources\BaseResource`                |
| Trait      | Extendable feature modules                        | `Wncms\Traits\HasTranslations`                     |
| Route      | System routes for web, backend, frontend, and API | `routes/backend.php`, `routes/frontend.php`        |

Each part of this section explains how to extend these layers properly.

## Development Environment

To extend WNCMS, ensure your Laravel app or package includes:

```bash
composer require secretwebmaster/wncms-core
```

If you are developing locally:

- Clone the package to `packages/secretwebmaster/wncms-core`
- Add it in `composer.json` via `"repositories"` section for local development
- Run `composer update`

## Next Steps

- [Model → Base Model](./model/base-model.md)
- [Controller → Backend Controller](./controller/backend-controller.md)
- [Manager → Base Manager](./manager/base-manager.md)
- [Locale → Localization Overview](./locale/localization-overview.md)

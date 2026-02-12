# WNCMS Unified To-Do

## wncms-v6 (secretwebmaster/wncms)

### Installation
- Check `composer.json` for redundant commands.

## wncms-core

### API
- In System Setting API tab, model API routes should pass package ID so translations can be resolved.
- Update API create-post flow:
- Post title should not be stored in JSON format.
- Allow skipping WebP conversion.
- Add API endpoint to create tag.
- Add API v1 endpoint to add domain to website model (example: `demo001.wndhcms.com`).

### Helper
- Remove useless helper functions and helper files.

### Manager
- Clean up and refactor manager code.
- Ensure custom managers in `App/Services/Managers/*Manager.php` are loaded correctly.

### Advertisement
- Add `type` filter support in Advertisement manager (example: `type=image`).
- Fix `AdvertisementController` order update issue.

### Hook / Plugin Events
- Implement/align hook system with reference:
- `https://github.com/cedar2025/Xboard/blob/master/docs/en/development/plugin-development-guide.md`
- `user.register.before` (Request) before user registration.
- `user.register.after` (User) after user registration.
- `user.login.after` (User) after user login.
- `user.password.reset.after` (User) after password reset.
- `order.cancel.before` (Order) before order cancellation.
- `order.cancel.after` (Order) after order cancellation.
- `payment.notify.before` (method, uuid, request) before payment callback.
- `payment.notify.verified` (array) after payment callback verification success.
- `payment.notify.failed` (method, uuid, request) after payment callback verification failed.
- `traffic.reset.after` (User) after traffic reset.
- `ticket.create.after` (Ticket) after ticket creation.
- `ticket.reply.user.after` (Ticket) after user ticket reply.
- `ticket.close.after` (Ticket) after ticket closure.

### Keyword
- Update backend routes from `v5.javmenu.com`.
- Update backend blade files from `v5.javmenu.com`.

### Language / Localization
- Configure LaravelLocalization in system settings and override runtime values in `WncmsServiceProvider`.
- Set default language during installation.
- Set default language in system settings.
- Fix `getAttribute` behavior for translated fields.
- Fix model translation issues.

### Menu
- Fix dropdown icon on edit page.
- Fix backend submenu reordering failure.
- Fix sub-level sorting failure.

### Model
- Add model method to check whether model is active.
- Fetch custom model class before fallback to `Wncms\Models\*` (prefer `wncms()->getModel('xxx')` flow).
- Add/confirm `Click`, `Parameter`, and `Channel` model extensions (Channel may integrate with affiliate system).

### OrderItem
- Allow more item types beyond `$this->getItemTypes()`.

### Page
- Allow type switching from array to text.

### Package
- Fetch package info from Packagist instead of composer command execution.
- Create job for package add/update; requires supervisor setup with root runner.
- Auto-set package inactive on index if package was removed by `composer remove` while marked active.

### Tag
- Fix TagController `Undefined variable $request`.
- Redesign keyword binding to map any model field to any tag type.
- Fix “all type” not showing all.
- Show only active-model tags in backend selection.
- Allow displaying custom tag names from composer packages.

### Theme
- If `app/theme` is deleted, fallback loading from package theme.
- Update Font Awesome search URL from `https://fontawesome.com/search` to `https://fontawesome.com/v6/search?ic=free&o=r`.
- Remove theme sync on every composer update.
- Add command to install default theme once during installation.
- Add backend button to reinstall theme and run that artisan command on demand.
- Update packaged theme files:
- Check file structure.
- Include language files in theme package.
- Allow `ThemeServiceProvider` injection.
- Improve theme activation checks/logic.

### Theme Option / Starter Form UI
- Update color input: remove `mb-5`, add `required`, add placeholder.
- Target file: `/www/wwwroot/cloak-001.com/vendor/secretwebmaster/wncms-core/resources/views/backend/parts/inputs.blade.php`.
- Fix starter template color input style (`#FFA218`).
- Add developer tips to starter form-item template.
- Add repeater field support.
- Fix overflow scrolling issue.
- Make each starter form item a separate blade and include in real model form-items.
- Localize `jquery.repeater.min.js` loading in `form-items.blade.php`; load only when needed.

### User
- Fix `https://demo.wncms.cc/user` error: Unknown named parameter `$fallback`.
- Prevent non-email values from being saved as email.
- Make telegram username required and configurable via system settings.
- Prevent duplicated usernames.
- Fix `profile.blade.php` undefined `$user` in `UserController@show_user_profile`.
- Separate frontend user login and admin login pages.

### Website
- Allow editing website domain.

### Link
- Remove `$q->orderBy('sort', 'desc')` when `$sort == 'random'`.
- Add `$q->with('media')` to prevent N+1.
- Fix link model permission issue.
- Run migration and assign permissions during update.
- Fix QuickLink regression.
- Update `LinkManager`.
- Allow link model form-item to update clicks.
- Add/confirm `Frontend\LinkController`.

### Permission / Mode
- Superadmin mode should show extra menu entries.
- Add multi-site mode switch in setting; when disabled, fallback to first website (`multi_website`).

### Backend UI
- Create common backend index page that can display all columns.
- Use translated column names when available.

### Installation
- Allow choosing template during installation.
- Allow choosing language during installation.
- Allow choosing multisite mode during installation (default `false`).
- Add `composer self-update` step to installation guide.

### Update
- Create tool to auto-fix update issues.
- Create tool to reinstall update files.

### Traits / Shortcodes
- Extend `HasShortCodes` to support shortcode `[wncms::bmi_calculattor]`.
- Render shortcode as inline BMI calculator widget (HTML + JS blade include).

### Testing
- Add tests that simulate package environments.
- Add tests that run in a real WNCMS project environment (example: `package.wncms.cc`) to reduce environment mismatch issues.

### Misc
- Review typo cleanup item (`operator`) and remove/fix meaningless task wording.
- Update starter HTML lang binding: `<html lang="{{ str_replace('_', '-', $wncms->locale()->getCurrentLocale()) }}" dir="ltr">`.
- Extract click summary as composer package (reference: `https://baishe01.wntheme.com/`).

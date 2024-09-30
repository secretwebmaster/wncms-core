# Wncms 紹介
デモ: [demo.wncms.cc](https://demo.wncms.cc)

## 更新履歴

### v4.5.0 (開発中)

### v4.4.0 2024-09-18
- バックエンドに更新通知機能を追加しました。
- モデル作成関数を更新し、カスタムモデルおよびコントローラーとの互換性を確保しました。
- モデルのページネーションを修正し、最後のページのアイテム数を正確に表示するようにしました。
- コアアップデートを無効にするオプションを設定に追加しました。
- `README.md` にデモURLを追加しました。
- エラーページを更新しました。
- 翻訳を追加しました。
- 設定を更新するコマンドを導入しました。
- テーマコントローラーを追加しました。

### v4.3.0 2024-09-14
- Tested new installation process
- Stopped tracking the vendor directory
- Updated the backend footer year to be dynamically updated
- Set default cache driver to null before installation
- Updated README.md
- Improved default template
- Updated installer icon. Added forced HTTPS setting automatically during installation
- Added error messages to the installer
- Added options to select Redis, Memcache, or file as cache drivers during installation
- Set default DB_CONNECTION to in-memory fake SQLite. Set default SESSION driver to file
- Disabled trait file generation during installation
- Added forced HTTPS option in system settings
- Added default env file
- Updated .gitignore
- Updated composer dependencies
- Updated theme options panel
- Improved first-time site creation process
- Removed debug messages
- Fixed backend styles
- Renamed Traits. Fixed installation errors. Added forced HTTPS compatibility
- Checked for the existence of custom language files before requirements

### v4.2.0 2024-09-13
- Removed WnTagTraits.php
- Added translations when generating demo post content
- Improved default homepage template. Added post archive URL
- Added more translations
- Fixed error when adding a second site

### v4.1.0 2024-09-07
- Added forced HTTPS option
- Minor fixes
- Updated default env file to hide debug messages
- Cleared cache after installation and site creation
- Added PHP function requirement checks
- Added database.sqlite to .gitignore
- Fixed create theme command. Updated lang directory according to Laravel 11 structure
- Fixed issue where ad expiration date could not be empty
- Removed unused UpdateLog model
- Updated default model settings
- Updated installer
- Added disable user registration feature
- Set theme to null instead of default on site edit page

### v4.0.0 2024-08-03
#### New
- PHP requirement upgraded from 7.2+ to 8.2+
- Laravel 10 -> 11
- jQuery 3.2.1 -> 3.7.1
- Updated all composer dependencies
- Added database prefix to avoid table name conflicts with MySQL reserved keywords
#### Fixes
- Fixed error on navigation panel/xxxx page when CMS is not installed
- Fixed php artisan migrate:fresh not working
- Fixed page controller error when $request-input is null
#### Optimizations
- Merged old migrations and renamed migration files
- Renamed database column external_image -> external_thumbnail
- Updated default env file generator
- Set default length of wncms_get_unique_slug() to 8
- Added space after all // comments. For example // Good and //Bad
- Rewrote login logic in wncms.js and AuthenticatedSessionController
#### Removed
- Removed vendor directory to reduce package size. Packages will be downloaded during installation
- Removed global function wn(), replaced with wncms()
- Removed global variable $wn, replaced with $wncms
- Removed analysis helper to avoid performance issues. Replaced with third-party analysis tools
- Removed wncms_get_theme_options() helper function. Use $website->get_options() in models

### v3.2.1 2024-05-19
#### New
- Added several flag SVGs
- Added more translation fields
- Added permission checks to data lists in backend modules
- Added menu checks for non-admin permissions in backend
- Reserved 1 proxy Dashboard for future development
#### Fixes
- Fixed some case errors
#### Optimizations
- common.table_status.blade.php can now determine more statuses, added custom features

### v3.2.0 2024-03-14
#### Fixes
- Fixed site_name null error during new version installation
#### Optimizations
- Updated installation process
- Treated api_token requests as logged in

### v3.1.15 2024-03-12
#### New
- Added API for Page module
- Added API for Menu module
- PostHelper getList() supports $isRandom parameter to retrieve random posts
- Added getTags to Post module to retrieve Tag list with $type parameter
- Added sorting feature to WebsiteHelper
- Optimized theme settings to differentiate between 0 and unset
- Added one-click complex menu feature
#### Fixes
- Fixed current_url item cache issue in contact form
- Fixed button text not displaying on form submission failure
#### Optimizations
- Planning to remove getFirstCategory() from Post module, use getFirstTag('post_category') instead
- Optimized mobile backend UI
- When generating theme files, automatically replace starter string with theme name without manual modification
- Theme names sorted alphabetically
- Before article search, check if keyword is included
- Optimized theme setting checks to differentiate between 0 and unset
- Display related module counts in backend Tags
- All Helpers default to no pagination $pageSize = 0

___
### v3.1.14 2023-12-17
#### New
- Added bulk copy post functionality

___
### v3.1.13 2023-12-12
#### New
- Added toolbar to module list to hide certain default buttons, e.g., 'hideToolbarStatusFiller' => true to hide status filter dropdown
- Added js event for contact form submissions, configurable for success and failure
- Added filter function to backend contact form
- Added export functionality to backend contact form (XLSX/CSV)
- Added translation fields
#### Fixes
- Fixed issue with decimal numbers in fake views
#### Optimizations
- Adjusted some backend UI elements

___
### v3.1.12 2023-12-11
#### New
- Added automatic conversion to webp when uploading cover images in system settings
- Added ExtraAttribute feature to modules, use getExtraAttribute() and saveExtraAttribute()
#### Fixes
- Fixed occasional duplicate display of switch descriptions in theme settings

___
### v3.1.11 2023-12-08
#### New
- Added more translation fields
#### Fixes
- Fixed issue with theme setting images not displaying if they have parentheses ()
#### Optimizations
- Added support for utm parameters to pages other than landing pages

___
### v3.1.10 2023-12-08
#### New
- Contact form supports adjusting field order
- Added several system settings, including super admin recipient email address
- Added SMTP test email feature
- Updated some translation fields
- Contact form now supports single and multiple selections
- Contact form now checks if all required fields are filled
#### Fixes
- Fixed mobile theme setting layout issues with long site names
- Fixed bulk delete issue in some modules
- Fixed article trash not updating status
#### Optimizations
- Updated default email template to support HTML

___
### v3.1.9 2023-12-06
#### New
- Added style.css and main.js for Starter theme
#### Fixes
- Fixed issue with some theme page components not loading
#### Optimizations
- When fetching posts, if excluded category IDs are specified, include posts without categories
- Updated form system to use new template calling method $wncms->contact_form()->render(), directly generating form structure
- Updated FAQ module to include more fields and sorting options

___
### v3.1.8 2023-11-29
#### New
- Added json-id structure

___
### v3.1.7 2023-11-21
#### New
- Optimized mobile UI for backend
- Uploaded image modules now support more image types
- Added one-click import of default theme settings
#### Fixes
- Fixed issue where article limit was less than pagination number, causing possible display issues
#### Optimizations
- Upgraded text editor in theme settings
- Updated translation fields

___
### v3.1.6 2023-11-17
#### New
- Bulk assign articles to sites feature
- Added click event logging with [wncms-click] attribute
- Added global site view count (distinct from article view count, where site view count is global and counts as 1 no matter how many articles are viewed)
- Added more translation fields
- Added system setting for view count and visit cooldown interval, configurable to 60 minutes or 1440 minutes, or custom minute input
- Added multiple filter conditions to ad positions

___
### v3.1.5 2023-11-16
#### New
- Menu items can now have descriptions
- [Developer] Added Widget system for creating Page templates, increasing reusability
- [Developer] Introduced Sortable JS plugin
- [Developer] Added stackable CSS position to backend backend.blade.php using @push('foot_css')
- Added more translation fields
- Added one-click demo post generation feature for development convenience
- [Developer] Added fourth common abbreviation function gpto (getPageTemplateOption) for retrieving individual page parameters
#### Fixes
- Fixed Ajax contact form return message, changed "Successfully created" to "Successfully submitted"
#### Optimizations
- Changed page group option field from json type to text type to prevent auto-sorting
- Modified single-page load order to template > plain > static, redirect to homepage if none exist
- Fixed menu links starting with # not properly jumping to anchors
- Fixed mobile backend theme settings styles
- [Developer] Significantly revised backend input.blade.php logic in preparation for visual editor

___
### v3.1.4 2023-11-04
#### Added
- [Developer] Added $wncms parameter in templates, equivalent to $wn
- [Developer] Added wncms()->paginateWithLimit() method to solve the issue of paginate not working with limit
- More translation strings
- Cache likes count, updated daily via scheduled task to the module like field, can speed up modules that do not need real-time likes count
- Updated RecordViews scheduled task to record any collection, such as visit, like, dislike
- [Developer] Added $wn->addVersion(), no need to add '?v=' . $wn->getVersion() in templates, new feature output already includes ?v=
- Introduced applicaion/ld+json option, can be pushed to <head>
#### Fixed
- Fixed issue where some pages did not load content after the 3.1.3 update

#### Optimized
- Default theme `<title>` changed from `<Home>` to website name
- Optimized prompt for adding same-name permissions, no longer an error but a notification of existing duplicate items
- [Developer] Renamed statistics section in front-end views: get_view -> get, record_like -> like, record_view -> record

___
### v3.1.3 2023-10-31
#### Added
- Updated backend menu module icons
- Added domain extraction from text
- Ads replaced with permanent ad setting
- [Developer] Added page template functionality, allowing developers to create Blade files for page management with one-click page generation
- Fixed color table options not displaying current values
- Added menu Tag item with one-click multi-language functionality, customizable display text for each language after retrieval
- Added more default ad positions
- [Developer] Added date marking module resources/views/common/table_date.blade.php, marked with different colors based on the distance from the current date and time
- [Developer] New function to check if a link is the same as the current page link, wncms()->isActiveUrl()
- Added more translation strings
- Added article ranking feature /posts/rank, /post/rank/{$period}, sortable by day, week, month, year
- [Developer] Added WnMediaTraits with one function $this->addThumbnailFromRequest(), allows direct image upload from request
- [Developer] Added getFirstTag() function in WnTgTraits to get the first Tag of any Model
- [Developer] CacheHelper new get() and put() functions
- [Developer] MenuHelper added theme_page link output, similar to external_link
- Added one-click ad copy function
#### Fixed
- [Developer] Fixed issue with overwritten Stub files after updating Laravel version, causing module generator templates to revert
- Fixed backend member page errors
- Fixed tag pagination displaying only the first page when cache is enabled
- Fixed issues with some modules not having initial permissions for administrators
- Fixed potential sorting issues after updating menu items
#### Optimized
- [Developer] Optimized link extraction from fields, optional to retain www, wncms()->getDomainFromString($string, $includePort = true, $preserveWWW = false)

___
### v3.1.2 2023-10-28
#### Added
- Used new Redis cache library, allowing individual tag clearing
- Added one-click cache clearing feature in the backend
- [Developer] Added method to get domain+port from String, wncms()->getDomainFromString()
#### Optimized
- [Developer] Refactored code
- [Developer] Updated Theme helper to not be affected by cache settings, still applies data cache time even if cache is disabled, default 3600 seconds, to prevent multiple database requests for theme settings after cache is disabled
- [Developer] Updated CSRF paths
- Fixed TinyMCE editor issue in theme settings
- [Developer] Added option in TagHelper to get only parent tags with getList()
- Updated article API
- Updated WNCMS installer, simplified process

___
### v3.1.1 2023-10-20
#### Added
- Redesigned installation process, simpler and faster
- [Developer] Updated tagify field in Starter to allow toggling switch search
- [Developer] Developers can customize admin control panel items
- [Developer] Developers can customize member homepage items
- Added more translation fields
#### Fixed
- Fixed issue with some modules not clearing tags
#### Optimized
- [Developer] Refactored backend Sidebar Model, developers can customize backend Model menu order by modifying $menuPriority property in Model
- [Developer] Updated controller.model.stub
- Rewritten member registration logic

___
### v3.0.8 2023-10-18
#### Added
- [Developer] Updated Wncms core class to allow arbitrary parameter setting $wn->anything = 'something';
- [Developer] Updated Wncms core class for more efficient reuse of Helper Classes

___
### v3.0.7 2023-10-17
#### Added
- Added bulk processing functionality to contact form, allowing batch marking as read, unread, or replied
- Added site-wide caching
- Added backup domain system
- Started adding data quantity information to some backend pages, such as total number and currently displayed data
- Added ad module
#### Optimized
- Updated Ajax button data submission mode
- Optimized system settings cache
- Upgraded TinyMCE editor, using Cloudflare CDN

___
### v3.0.6 2023-10-09
#### Added
- Added more translation fields
- [Developer] Added table_url Blade view
- [Developer] Added demonstration module to web.php route
- [Developer] Added re-manufacture button to starter/index.blade.php view
- [Developer] Added WnTagTraits feature to Tag module with getAvailableTypes() to retrieve taxonomy list ['post_tag', 'video_category', etc]

___
### v3.0.5 2023-10-07
#### Added
- Checked if custom modules exist (Custom{Model}Traits.php)

___
### v3.0.4 2023-10-07
#### Fixed
- Fixed minor issues with some modules
#### Optimized
- Optimized backend UI

___
### v3.0.3 2023-10-06
#### Optimized
- Updated article module, fixed member permission issues
- Moved Trait directory to Ap directory

___
### v3.0.2 2023-09-30
#### Added
- Added Page type (Plain text type, template created by editor, template provided by theme)

___
### 2.0.0 2021-07-28
- New version release! (Related update logs are archived)

___
### 1.0.0 2019-03-01
- Initial version release (Related update logs are archived)

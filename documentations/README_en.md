# Wncms Introduction

Demo: [demo.wncms.cc](https://demo.wncms.cc)

## Changelog

### v5.5.5 2025-09-08

-   Update AdvertisementManager to extend ModelManager
-   Remove duplicated translation migration file
-   Fix Advertisement website relationship not found
-   Load frontend theme helpers in PageController
-   Move HasTags trait to base WncmsModel (all models can now be taggable)
-   Remove redundant OwnModelTrait and WnModelTrait
-   Remove ExtraAttribute and replace with laravel-optionable package
-   Add tools page in backend
-   Localize @simonwep/pickr package
-   Refactor all controller view() functions to use wncms()->view()
-   Add contact field to Advertisement model
-   Add API to create Tag model
-   Add HasComments trait
-   Add comment list display to backend Post
-   Rewrite Frontend PostController to fit latest format
-   Fix backend permission edit page error
-   Prepare backend UI for API usage logging

### v5.5.4 2025-08-18

-   Fix translation issue when saving tags, update dependency **wncms-translatable**
-   Fix multiple validation and data passing issues in **UserController** (`profile` page missing `$user`, wrong password validation rules/messages, incorrect `withError()` usage, optimized `profile` update)
-   Fix issues in multisite mode
-   Update **Controller stub** files to fit new structure
-   Fix backend controller route errors

### v5.5.3 2025-08-17

-   Fix issue where submitting Checkbox sends NULL value
-   Beta version: compatibility for multi-module multisite
-   Update Starter form item Blade repeater example
-   Rename WnContentModelTraits to WnContentModelTrait
-   Rename WnModelTraits to WnModelTrait
-   Beta version: add HasMultisite trait

### v5.5.2 2025-07-20

-   Add frontend link page and LinkController
-   Update dependency wncms-tags > 1.6.1

### v5.5.0 2025-07-19

-   Replaced `getVersion()` with `addVersion()` in some backend Blade views
-   Added `isActiveRoutes()` helper
-   Fixed installation process halting after database migration
-   Prevented duplicate models in sidebar when overriding `Wncms\Models` with `App\Models`
-   Fixed custom resource pages not found in `PageController`
-   Removed `click.js`; replaced with API integration
-   Updated version number after fresh installation
-   Queue connection is now set to `sync` by default instead of `redis`
-   Delete actions now redirect back instead of returning the index page
-   Rewrote `PageManager`
-   Rewrote `UserManager`
-   Fixed high memory usage on dashboard for large databases
-   Cleaned up backend `PostController` code
-   Allowed overriding backend views via app view paths before falling back to WNCMS views
-   Developer hints are now hidden when input fields are of type `hidden`
-   Fixed missing background color value for advertisements
-   Merged create and edit form items in the backend contact form
-   Removed `BulkDeleteTrait` (now integrated into `BackendController`)
-   Removed `tags.index.type` route; integrated into `tags.index`
-   Removed multisite option from menu
-   Renamed product field `attribute` to `properties`
-   Added support for custom search keywords
-   Removed `traffic_log` and `click_log` Blade views
-   Updated all model relationships to use `wncms()->getModelClass('xxx')`, allowing model overrides
-   Updated `CreateModel` route structure to use `{id}` instead of `{modelName}`
-   Updated controller stub to include `order by id desc`
-   Improved backend website edit view; added domain alias sorting

### v5.4.3 2025-07-17

-   Backend link routes accept id instead of auto resolving model. Allow to use custom Link model.
-   BackendController now become abstract class to maintain standard
-   Add `name` support to ModelManager get() function $option variable
-   Domain that not in the list will now share same cache will fallback website
-   Automatically add ordering column in select to avoid sql error
-   Eagar load website media on fallback website in WebsiteManager
-   Rewrite TagManager where condition logic
-   Update update log api route name to `api.v1.update.run`
-   Update Link model description input field to textarea
-   Clean up backend routes code. Remove starting slash
-   Add abstract getModelClass() method to BackendController
-   Remove AnalyticController

### v5.4.0 2025-05-01

-   Fix issue where Card model status was not found
-   Add dropdown list to select website model when `multi_website` is enabled
-   Support model-level constant `NAME_KEY` to define display name (avoids translation conflicts like "order")
-   Update model icons on backend sidebar menu
-   Allow deletion of website models except the last one
-   Add bulk delete support for permissions
-   Add Channel, Click, and Parameter models
-   Temporarily hide the Price model; prices should be defined in the parent model
-   Fix undefined `$display_name` variable
-   Remove unused analytics API route
-   Add table existence check before running most migrations
-   Eager load media in LinkManager
-   Refactor CacheManager with modern syntax and support for tag-based get/put/forget
-   Refactor ModelManager as base class for shared model logic
-   Refactor PostManager and LinkManager to extend ModelManager and support tag/keyword filters
-   Support `wncms()->getModel('xxx')` with fallback to config/App/Models/Wncms/Models
-   Improve translation keys (`bulk_create`, `channel`, `parameters`, `clickable_type`, etc.) in `zh_TW`
-   Update CardController to use dynamic user model from `wncms()->getModel('user')`
-   Allow `gto()` to return all options when key is empty
-   Add translations
-   Fix typos: `fontaweseom` → `fontawesome`, `operater` → `operator`, `Starer` → `Starter`
-   Add `$tag->url` accessor to Tag model
-   Update backend user search logic
-   Improve backend advertisement index with pagination
-   Fix `pack theme file` command to include language files
-   Add debug function `wncms()->log($message, $level)`
-   [Important] Upgrade LinkManager to extend ModelManager
-   Fix quick link removal in backend header
-   Add `repeater` input to `starter/form-items.blade.php`
-   Fix color input error in `starter/form-items.blade.php`
-   Add developer tips to `form-items.blade.php`
-   Fix advertisement order not saving
-   Separate user login and admin login pages
-   Update Post API

### v5.3.0 2025-03-30

-   Header website links display according to multisite setting
-   Fix advertisement model not updating order field
-   Allow domain of website model can be updated
-   Add system setting of superadmin. Prepare to hide some less frequently used options
-   Update theme list icon on backend sidebar
-   Add theme file structure checking
-   Load custom ModelManager prior to package ModelManager
-   Allow passing nullable value to get() of TagManager to prevent error when null value is fetched from theme option
-   Update backend index pages status and boolean field style. Allow use badge or text
-   Set default page size of getList() funciton to 0 which pagination is disabled by default
-   Add bulk model update button blade that can be included in backend index
-   Update Link model backend. Allow bulk update status and order

### v5.2.0 2025-02-04

-   Add Translations
-   Code clean up
-   Assign member role to frontend registered user by default. Allow modify from theme option
-   Fetch first website advertisement when website model is not found by current domain
-   Eagar load link translation
-   Fetch posts of first website model if no model is found by current domain. Allow order by random to query result
-   Eagar load tranlsations of website model

### v5.1.3 2025-02-04

-   Add translations

### v5.1.2 2025-02-04

-   Improve backend quick link saving logic. Save url instead of route name to avoid missing parameters
-   Add website id to backend theme option page title

### v5.1.1 2025-01-28

-   Update all gss version to core_version

### v5.1.0 2025-01-28

-   Fixed more issues where menu item data could not be saved
-   Removed the `required` class from nullable fields
-   Preloaded the `Website` model and its translations
-   Added `ThemeServiceProvider`
-   Displayed page URLs using the current domain instead of retrieving them from the `website` model
-   Preloaded the translations of the `Post` model and allowed the `order` variable to be set to random
-   Made the `Link` model translatable

### v5.0.0 2025-01-27

-   Fixed incorrect route order on the user page
-   Added functions in the `Credit` model to add or retrieve credit amounts
-   Added a function in the `User` model to directly check the credit amount
-   Allowed TinyMCE selector to be a class and added GIF to the list of allowed image formats
-   Re-enabled the ability to log into the backend using the frontend login page
-   Dynamically modified CSRF rules at runtime
-   Updated dependency for `wncms-translatable`
-   Fixed missing menu item translations after update
-   Allowed `gto()` to be configured to fall back when the value is empty
-   Added CRUD (Create, Read, Update, Delete) functionality for frontend posts

### v4.5.0 2024-09-30

-   Fix paginated url is cached with locale
-   Add plugin system
-   Improve PostHelper performance
-   Update Tests.
-   Add LOCALIZATION_HIDE_DEFAULT_LOCALE env variable

### v4.4.0 2024-09-18

-   Added an update notification function to the backend.
-   Updated model creation functions to ensure compatibility with custom models and controllers.
-   Fixed model pagination to accurately display the item count on the last page within the limit.
-   Added an option in the settings to disable core updates.
-   Included a demo URL in `README.md`.
-   Updated error pages.
-   Added translations.
-   Introduced a command to update settings.
-   Added a Theme Controller.

### v4.3.0 2024-09-14

-   Tested new installation process
-   Stopped tracking the vendor directory
-   Updated the backend footer year to be dynamically updated
-   Set default cache driver to null before installation
-   Updated README.md
-   Improved default template
-   Updated installer icon. Added forced HTTPS setting automatically during installation
-   Added error messages to the installer
-   Added options to select Redis, Memcache, or file as cache drivers during installation
-   Set default DB_CONNECTION to in-memory fake SQLite. Set default SESSION driver to file
-   Disabled trait file generation during installation
-   Added forced HTTPS option in system settings
-   Added default env file
-   Updated .gitignore
-   Updated composer dependencies
-   Updated theme options panel
-   Improved first-time site creation process
-   Removed debug messages
-   Fixed backend styles
-   Renamed Traits. Fixed installation errors. Added forced HTTPS compatibility
-   Checked for the existence of custom language files before requirements

### v4.2.0 2024-09-13

-   Removed WnTagTraits.php
-   Added translations when generating demo post content
-   Improved default homepage template. Added post archive URL
-   Added more translations
-   Fixed error when adding a second site

### v4.1.0 2024-09-07

-   Added forced HTTPS option
-   Minor fixes
-   Updated default env file to hide debug messages
-   Cleared cache after installation and site creation
-   Added PHP function requirement checks
-   Added database.sqlite to .gitignore
-   Fixed create theme command. Updated lang directory according to Laravel 11 structure
-   Fixed issue where ad expiration date could not be empty
-   Removed unused UpdateLog model
-   Updated default model settings
-   Updated installer
-   Added disable user registration feature
-   Set theme to null instead of default on site edit page

### v4.0.0 2024-08-03

#### New

-   PHP requirement upgraded from 7.2+ to 8.2+
-   Laravel 10 -> 11
-   jQuery 3.2.1 -> 3.7.1
-   Updated all composer dependencies
-   Added database prefix to avoid table name conflicts with MySQL reserved keywords

#### Fixes

-   Fixed error on navigation panel/xxxx page when CMS is not installed
-   Fixed php artisan migrate:fresh not working
-   Fixed page controller error when $request-input is null

#### Optimizations

-   Merged old migrations and renamed migration files
-   Renamed database column external_image -> external_thumbnail
-   Updated default env file generator
-   Set default length of wncms_get_unique_slug() to 8
-   Added space after all // comments. For example // Good and //Bad
-   Rewrote login logic in wncms.js and AuthenticatedSessionController

#### Removed

-   Removed vendor directory to reduce package size. Packages will be downloaded during installation
-   Removed global function wn(), replaced with wncms()
-   Removed global variable $wn, replaced with $wncms
-   Removed analysis helper to avoid performance issues. Replaced with third-party analysis tools
-   Removed wncms_get_theme_options() helper function. Use $website->get_options() in models

### v3.2.1 2024-05-19

#### New

-   Added several flag SVGs
-   Added more translation fields
-   Added permission checks to data lists in backend modules
-   Added menu checks for non-admin permissions in backend
-   Reserved 1 proxy Dashboard for future development

#### Fixes

-   Fixed some case errors

#### Optimizations

-   common.table_status.blade.php can now determine more statuses, added custom features

### v3.2.0 2024-03-14

#### Fixes

-   Fixed site_name null error during new version installation

#### Optimizations

-   Updated installation process
-   Treated api_token requests as logged in

### v3.1.15 2024-03-12

#### New

-   Added API for Page module
-   Added API for Menu module
-   PostHelper getList() supports $isRandom parameter to retrieve random posts
-   Added getTags to Post module to retrieve Tag list with $type parameter
-   Added sorting feature to WebsiteHelper
-   Optimized theme settings to differentiate between 0 and unset
-   Added one-click complex menu feature

#### Fixes

-   Fixed current_url item cache issue in contact form
-   Fixed button text not displaying on form submission failure

#### Optimizations

-   Planning to remove getFirstCategory() from Post module, use getFirstTag('post_category') instead
-   Optimized mobile backend UI
-   When generating theme files, automatically replace starter string with theme name without manual modification
-   Theme names sorted alphabetically
-   Before article search, check if keyword is included
-   Optimized theme setting checks to differentiate between 0 and unset
-   Display related module counts in backend Tags
-   All Helpers default to no pagination $pageSize = 0

### v3.1.14 2023-12-17

#### New

-   Added bulk copy post functionality

### v3.1.13 2023-12-12

#### New

-   Added toolbar to module list to hide certain default buttons, e.g., 'hideToolbarStatusFiller' => true to hide status filter dropdown
-   Added js event for contact form submissions, configurable for success and failure
-   Added filter function to backend contact form
-   Added export functionality to backend contact form (XLSX/CSV)
-   Added translation fields

#### Fixes

-   Fixed issue with decimal numbers in fake views

#### Optimizations

-   Adjusted some backend UI elements

### v3.1.12 2023-12-11

#### New

-   Added automatic conversion to webp when uploading cover images in system settings
-   Added ExtraAttribute feature to modules, use getExtraAttribute() and saveExtraAttribute()

#### Fixes

-   Fixed occasional duplicate display of switch descriptions in theme settings

### v3.1.11 2023-12-08

#### New

-   Added more translation fields

#### Fixes

-   Fixed issue with theme setting images not displaying if they have parentheses ()

#### Optimizations

-   Added support for utm parameters to pages other than landing pages

### v3.1.10 2023-12-08

#### New

-   Contact form supports adjusting field order
-   Added several system settings, including super admin recipient email address
-   Added SMTP test email feature
-   Updated some translation fields
-   Contact form now supports single and multiple selections
-   Contact form now checks if all required fields are filled

#### Fixes

-   Fixed mobile theme setting layout issues with long site names
-   Fixed bulk delete issue in some modules
-   Fixed article trash not updating status

#### Optimizations

-   Updated default email template to support HTML

### v3.1.9 2023-12-06

#### New

-   Added style.css and main.js for Starter theme

#### Fixes

-   Fixed issue with some theme page components not loading

#### Optimizations

-   When fetching posts, if excluded category IDs are specified, include posts without categories
-   Updated form system to use new template calling method $wncms->contact_form()->render(), directly generating form structure
-   Updated FAQ module to include more fields and sorting options

### v3.1.8 2023-11-29

#### New

-   Added json-id structure

### v3.1.7 2023-11-21

#### New

-   Optimized mobile UI for backend
-   Uploaded image modules now support more image types
-   Added one-click import of default theme settings

#### Fixes

-   Fixed issue where article limit was less than pagination number, causing possible display issues

#### Optimizations

-   Upgraded text editor in theme settings
-   Updated translation fields

### v3.1.6 2023-11-17

#### New

-   Bulk assign articles to sites feature
-   Added click event logging with [wncms-click] attribute
-   Added global site view count (distinct from article view count, where site view count is global and counts as 1 no matter how many articles are viewed)
-   Added more translation fields
-   Added system setting for view count and visit cooldown interval, configurable to 60 minutes or 1440 minutes, or custom minute input
-   Added multiple filter conditions to ad positions

### v3.1.5 2023-11-16

#### New

-   Menu items can now have descriptions
-   [Developer] Added Widget system for creating Page templates, increasing reusability
-   [Developer] Introduced Sortable JS plugin
-   [Developer] Added stackable CSS position to backend backend.blade.php using @push('foot_css')
-   Added more translation fields
-   Added one-click demo post generation feature for development convenience
-   [Developer] Added fourth common abbreviation function gpto (getPageTemplateOption) for retrieving individual page parameters

#### Fixes

-   Fixed Ajax contact form return message, changed "Successfully created" to "Successfully submitted"

#### Optimizations

-   Changed page group option field from json type to text type to prevent auto-sorting
-   Modified single-page load order to template > plain > static, redirect to homepage if none exist
-   Fixed menu links starting with # not properly jumping to anchors
-   Fixed mobile backend theme settings styles
-   [Developer] Significantly revised backend input.blade.php logic in preparation for visual editor

### v3.1.4 2023-11-04

#### Added

-   [Developer] Added $wncms parameter in templates, equivalent to $wn
-   [Developer] Added wncms()->paginateWithLimit() method to solve the issue of paginate not working with limit
-   More translation strings
-   Cache likes count, updated daily via scheduled task to the module like field, can speed up modules that do not need real-time likes count
-   Updated RecordViews scheduled task to record any collection, such as visit, like, dislike
-   [Developer] Added $wn->addVersion(), no need to add '?v=' . $wn->getVersion() in templates, new feature output already includes ?v=
-   Introduced applicaion/ld+json option, can be pushed to <head>

#### Fixed

-   Fixed issue where some pages did not load content after the 3.1.3 update

#### Optimized

-   Default theme `<title>` changed from `<Home>` to website name
-   Optimized prompt for adding same-name permissions, no longer an error but a notification of existing duplicate items
-   [Developer] Renamed statistics section in front-end views: get_view -> get, record_like -> like, record_view -> record

### v3.1.3 2023-10-31

#### Added

-   Updated backend menu module icons
-   Added domain extraction from text
-   Ads replaced with permanent ad setting
-   [Developer] Added page template functionality, allowing developers to create Blade files for page management with one-click page generation
-   Fixed color table options not displaying current values
-   Added menu Tag item with one-click multi-language functionality, customizable display text for each language after retrieval
-   Added more default ad positions
-   [Developer] Added date marking module resources/views/common/table_date.blade.php, marked with different colors based on the distance from the current date and time
-   [Developer] New function to check if a link is the same as the current page link, wncms()->isActiveUrl()
-   Added more translation strings
-   Added article ranking feature /posts/rank, /post/rank/{$period}, sortable by day, week, month, year
-   [Developer] Added WnMediaTraits with one function $this->addThumbnailFromRequest(), allows direct image upload from request
-   [Developer] Added getFirstTag() function in WnTgTraits to get the first Tag of any Model
-   [Developer] CacheHelper new get() and put() functions
-   [Developer] MenuHelper added theme_page link output, similar to external_link
-   Added one-click ad copy function

#### Fixed

-   [Developer] Fixed issue with overwritten Stub files after updating Laravel version, causing module generator templates to revert
-   Fixed backend member page errors
-   Fixed tag pagination displaying only the first page when cache is enabled
-   Fixed issues with some modules not having initial permissions for administrators
-   Fixed potential sorting issues after updating menu items

#### Optimized

-   [Developer] Optimized link extraction from fields, optional to retain www, wncms()->getDomainFromString($string, $includePort = true, $preserveWWW = false)

### v3.1.2 2023-10-28

#### Added

-   Used new Redis cache library, allowing individual tag clearing
-   Added one-click cache clearing feature in the backend
-   [Developer] Added method to get domain+port from String, wncms()->getDomainFromString()

#### Optimized

-   [Developer] Refactored code
-   [Developer] Updated Theme helper to not be affected by cache settings, still applies data cache time even if cache is disabled, default 3600 seconds, to prevent multiple database requests for theme settings after cache is disabled
-   [Developer] Updated CSRF paths
-   Fixed TinyMCE editor issue in theme settings
-   [Developer] Added option in TagHelper to get only parent tags with getList()
-   Updated article API
-   Updated WNCMS installer, simplified process

### v3.1.1 2023-10-20

#### Added

-   Redesigned installation process, simpler and faster
-   [Developer] Updated tagify field in Starter to allow toggling switch search
-   [Developer] Developers can customize admin control panel items
-   [Developer] Developers can customize member homepage items
-   Added more translation fields

#### Fixed

-   Fixed issue with some modules not clearing tags

#### Optimized

-   [Developer] Refactored backend Sidebar Model, developers can customize backend Model menu order by modifying $menuPriority property in Model
-   [Developer] Updated controller.model.stub
-   Rewritten member registration logic

### v3.0.8 2023-10-18

#### Added

-   [Developer] Updated Wncms core class to allow arbitrary parameter setting $wn->anything = 'something';
-   [Developer] Updated Wncms core class for more efficient reuse of Helper Classes

### v3.0.7 2023-10-17

#### Added

-   Added bulk processing functionality to contact form, allowing batch marking as read, unread, or replied
-   Added site-wide caching
-   Added backup domain system
-   Started adding data quantity information to some backend pages, such as total number and currently displayed data
-   Added ad module

#### Optimized

-   Updated Ajax button data submission mode
-   Optimized system settings cache
-   Upgraded TinyMCE editor, using Cloudflare CDN

### v3.0.6 2023-10-09

#### Added

-   Added more translation fields
-   [Developer] Added table_url Blade view
-   [Developer] Added demonstration module to web.php route
-   [Developer] Added re-manufacture button to starter/index.blade.php view
-   [Developer] Added WnTagTraits feature to Tag module with getAvailableTypes() to retrieve taxonomy list ['post_tag', 'video_category', etc]

### v3.0.5 2023-10-07

#### Added

-   Checked if custom modules exist (Custom{Model}Traits.php)

### v3.0.4 2023-10-07

#### Fixed

-   Fixed minor issues with some modules

#### Optimized

-   Optimized backend UI

### v3.0.3 2023-10-06

#### Optimized

-   Updated article module, fixed member permission issues
-   Moved Trait directory to Ap directory

### v3.0.2 2023-09-30

#### Added

-   Added Page type (Plain text type, template created by editor, template provided by theme)

### 2.0.0 2021-07-28

-   New version release! (Related update logs are archived)

### 1.0.0 2019-03-01

-   Initial version release (Related update logs are archived)

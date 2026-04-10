<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class I18nController extends ApiV2Controller
{
    public function ui(Request $request)
    {
        $supported = (array) LaravelLocalization::getSupportedLocales();
        $current = $this->normalizeLocale((string) ($request->input('locale') ?: app()->getLocale()));
        $fallback = $this->normalizeLocale((string) app()->getFallbackLocale());

        if (!array_key_exists($current, $supported)) {
            $current = array_key_first($supported) ?: $fallback;
        }

        app()->setLocale($current);

        $locales = [];
        foreach ($supported as $code => $meta) {
            $normalized = $this->normalizeLocale((string) $code);
            $locales[] = [
                'code' => $normalized,
                'native' => (string) ($meta['native'] ?? $normalized),
                'name' => (string) ($meta['name'] ?? $normalized),
                'regional' => (string) ($meta['regional'] ?? ''),
            ];
        }

        return $this->ok([
            'locale' => $current,
            'fallback_locale' => $fallback,
            'locales' => $locales,
            'messages' => $this->buildUiMessages(),
        ]);
    }

    public function translations(Request $request)
    {
        $namespace = trim((string) $request->input('namespace', 'wncms'));
        $group = trim((string) $request->input('group', 'word'));
        $key = trim((string) $request->input('key', ''));
        $supported = (array) LaravelLocalization::getSupportedLocales();
        $current = $this->normalizeLocale((string) ($request->input('locale') ?: app()->getLocale()));
        $fallback = $this->normalizeLocale((string) app()->getFallbackLocale());

        if ($namespace === '' || $group === '') {
            return $this->error('namespace and group are required', 422);
        }

        if (!array_key_exists($current, $supported)) {
            $current = array_key_first($supported) ?: $fallback;
        }

        app()->setLocale($current);
        $translations = $this->fetchGroupTranslations($namespace, $group, $current, $fallback);
        $locales = $this->buildLocaleOptions($supported);

        if ($key !== '') {
            return $this->ok([
                'locale' => $current,
                'fallback_locale' => $fallback,
                'namespace' => $namespace,
                'group' => $group,
                'key' => $key,
                'locales' => $locales,
                'value' => $this->resolveTranslationValue($namespace, $group, $key, $current, $fallback),
            ]);
        }

        return $this->ok([
            'locale' => $current,
            'fallback_locale' => $fallback,
            'namespace' => $namespace,
            'group' => $group,
            'locales' => $locales,
            'translations' => $translations,
        ]);
    }

    protected function buildUiMessages(): array
    {
        $word = fn (string $key, string $fallback): string => (string) __(
            'wncms::word.' . $key
        ) !== 'wncms::word.' . $key ? (string) __('wncms::word.' . $key) : $fallback;

        return [
            // Nav / shell
            'nav_menu' => $word('menu', 'Menu'),
            'nav_admin' => $word('admin', 'Admin'),
            'nav_content' => $word('content', 'Content'),
            'nav_extensions' => $word('extensions', 'Extensions'),
            'nav_dashboard' => $word('dashboard', 'Dashboard'),
            'nav_routes' => $word('routes', 'Routes Explorer'),
            'nav_posts' => $word('posts', 'Posts'),
            'nav_tools' => $word('tools', 'Tools'),
            'nav_route_parity' => $word('route_parity', 'Route parity in progress'),
            'topbar_search_placeholder' => $word('search_or_type_command', 'Search or type command...'),
            'topbar_api_routes' => $word('api_routes', 'API Routes'),
            'topbar_logout' => $word('logout', 'Logout'),
            'topbar_manage_posts' => $word('manage_posts', 'Manage posts'),
            'footer_desc' => $word('backend_api_v2_nextjs_admin_shell', 'Backend API v2 + Next.js admin shell'),
            'label_next_admin' => $word('next_admin', 'Next Admin'),

            // Sidebar resources
            'resource_users' => $word('users', 'Users'),
            'resource_roles' => $word('roles', 'Roles'),
            'resource_permissions' => $word('permissions', 'Permissions'),
            'resource_settings' => $word('settings', 'Settings'),
            'resource_websites' => $word('websites', 'Websites'),
            'resource_pages' => $word('pages', 'Pages'),
            'resource_posts' => $word('posts', 'Posts'),
            'resource_tags' => $word('tags', 'Tags'),
            'resource_menus' => $word('menus', 'Menus'),
            'resource_links' => $word('links', 'Links'),
            'resource_comments' => $word('comments', 'Comments'),
            'resource_search_keywords' => $word('search_keywords', 'Search Keywords'),
            'resource_channels' => $word('channels', 'Channels'),
            'resource_advertisements' => $word('advertisements', 'Advertisements'),
            'resource_parameters' => $word('parameters', 'Parameters'),
            'resource_plugins' => $word('plugins', 'Plugins'),
            'resource_packages' => $word('packages', 'Packages'),
            'resource_themes' => $word('themes', 'Themes'),
            'resource_uploads' => $word('uploads', 'Uploads'),
            'resource_updates' => $word('updates', 'Updates'),
            'resource_cache' => $word('cache', 'Cache'),
            'resource_records' => $word('records', 'Records'),
            'resource_clicks' => $word('clicks', 'Clicks'),
            'nav_tag_keywords' => $word('tag_keywords', 'Tag Keywords'),
            'nav_create_tag_type' => $word('create_tag_type', 'Create Tag Type'),
            'nav_bulk_create_tags' => $word('bulk_create_tags', 'Bulk Create Tags'),

            // Common labels / buttons used by forms
            'word_basic' => $word('basic', 'Basic'),
            'word_comments' => $word('comments', 'Comments'),
            'word_title' => $word('title', 'Title'),
            'word_slug' => $word('slug', 'Slug'),
            'word_status' => $word('status', 'Status'),
            'word_visibility' => $word('visibility', 'Visibility'),
            'word_label' => $word('label', 'Label'),
            'word_excerpt' => $word('excerpt', 'Excerpt'),
            'word_content' => $word('content', 'Content'),
            'word_remark' => $word('remark', 'Remark'),
            'word_price' => $word('price', 'Price'),
            'word_sort' => $word('sort', 'Sort'),
            'word_password' => $word('password', 'Password'),
            'word_external_thumbnail' => $word('external_thumbnail', 'External Thumbnail'),
            'word_thumbnail' => $word('thumbnail', 'Thumbnail'),
            'word_published_at' => $word('published_at', 'Published At'),
            'word_expired_at' => $word('expired_at', 'Expired At'),
            'word_author' => $word('author', 'Author'),
            'word_websites' => $word('websites', 'Websites'),
            'word_categories' => $word('categories', 'Categories'),
            'word_tags' => $word('tags', 'Tags'),
            'word_create' => $word('create', 'Create'),
            'word_edit' => $word('edit', 'Edit'),
            'word_update' => $word('update', 'Update'),
            'word_publish' => $word('publish', 'Publish'),
            'word_back' => $word('back', 'Back'),
            'word_preview' => $word('preview', 'Preview'),
            'word_refresh' => $word('refresh', 'Refresh'),
            'word_delete' => $word('delete', 'Delete'),
            'word_submit' => $word('submit', 'Submit'),
            'word_cancel' => $word('cancel', 'Cancel'),
            'word_search' => $word('search', 'Search'),
            'word_name' => $word('name', 'Name'),
            'word_guard' => $word('guard', 'Guard'),
            'word_permissions' => $word('permissions', 'Permissions'),
            'word_loading' => $word('loading', 'Loading...'),
            'word_saving' => $word('saving', 'Saving...'),
            'word_manage' => $word('manage', 'Manage'),
            'word_total' => $word('total', 'Total'),
            'word_shown' => $word('shown', 'Shown'),
            'word_records' => $word('records', 'records'),
            'word_action' => $word('action', 'Action'),
            'word_resource' => $word('resource', 'Resource'),
            'word_id' => $word('id', 'ID'),
            'word_updated_at' => $word('updated_at', 'Updated'),
            'word_bulk_delete_selected' => $word('bulk_delete_selected', 'Bulk Delete Selected'),
            'word_new_role' => $word('new_role', 'New Role'),
            'word_role_name' => $word('role_name', 'Role Name'),
            'word_select_permissions_for_role' => $word('select_permissions_for_role', 'Select permissions for this role'),
            'word_loaded_via_list_fallback' => $word('loaded_via_list_fallback', 'Loaded via list fallback.'),
            'word_update_payload_json' => $word('update_payload_json', 'Update Payload (JSON)'),
            'word_create_payload_json' => $word('create_payload_json', 'Create Payload (JSON)'),
            'word_delete_id' => $word('delete_id', 'Delete ID'),
            'word_update_id' => $word('update_id', 'Update ID'),
            'word_json_workbench' => $word('json_workbench', 'JSON Workbench'),
            'word_loaded' => $word('loaded', 'Loaded'),

            // Page subtitles / statuses
            'subtitle_roles_page' => $word('subtitle_roles_page', 'Dedicated role management with permission assignment'),
            'subtitle_create_role' => $word('subtitle_create_role', 'Create role via backend bridge endpoint'),
            'subtitle_edit_role' => $word('subtitle_edit_role', 'Edit role via backend bridge endpoint'),
            'subtitle_manage_resource_generic' => $word('subtitle_manage_resource_generic', 'Manage resource using backend API v2'),
            'subtitle_create_resource_generic' => $word('subtitle_create_resource_generic', 'Create resource via backend API v2'),
            'subtitle_edit_resource_generic' => $word('subtitle_edit_resource_generic', 'Edit resource via backend API v2'),
            'subtitle_create_parameter' => $word('subtitle_create_parameter', 'Equivalent to backend parameters create'),
            'subtitle_edit_parameter' => $word('subtitle_edit_parameter', 'Equivalent to backend parameters edit'),
            'subtitle_create_channel' => $word('subtitle_create_channel', 'Equivalent to backend channels create'),
            'subtitle_edit_channel' => $word('subtitle_edit_channel', 'Equivalent to backend channels edit'),
            'subtitle_search_keywords_page' => $word('subtitle_search_keywords_page', 'Equivalent to backend search_keywords index'),
            'subtitle_create_search_keyword' => $word('subtitle_create_search_keyword', 'Equivalent to backend search_keywords create'),
            'subtitle_edit_search_keyword' => $word('subtitle_edit_search_keyword', 'Equivalent to backend search_keywords edit'),
            'subtitle_create_post' => $word('subtitle_create_post', 'TailAdmin-inspired post form backed by the dedicated Post API v2 controller'),
            'subtitle_edit_post' => $word('subtitle_edit_post', 'Edit flow now uses Post-specific backend API logic and payload formatting'),
            'subtitle_create_page' => $word('subtitle_create_page', 'Form-items style page editor (create)'),
            'subtitle_edit_page' => $word('subtitle_edit_page', 'Form-items style page editor (edit)'),
            'subtitle_create_tag' => $word('subtitle_create_tag', 'Form-items style tag editor (create)'),
            'subtitle_edit_tag' => $word('subtitle_edit_tag', 'Form-items style tag editor (edit)'),
            'subtitle_create_advertisement' => $word('subtitle_create_advertisement', 'Equivalent to backend advertisements create'),
            'subtitle_edit_advertisement' => $word('subtitle_edit_advertisement', 'Equivalent to backend advertisements edit'),
            'subtitle_create_tag_type' => $word('subtitle_create_tag_type', 'Equivalent to backend tags type create route'),
            'subtitle_bulk_create_tags' => $word('subtitle_bulk_create_tags', 'Equivalent to backend tags bulk create route'),
            'title_create_role' => $word('title_create_role', 'Create Role'),
            'title_manage_roles' => $word('title_manage_roles', 'Manage roles'),
            'title_create_user' => $word('title_create_user', 'Create User'),
            'title_edit_user_with_id' => $word('title_edit_user_with_id', 'Edit User #{id}'),
            'title_edit_menu_with_id' => $word('title_edit_menu_with_id', 'Edit Menu #{id}'),
            'title_create_parameter' => $word('title_create_parameter', 'Create Parameter'),
            'title_edit_parameter_with_id' => $word('title_edit_parameter_with_id', 'Edit Parameter #{id}'),
            'title_create_channel' => $word('title_create_channel', 'Create Channel'),
            'title_edit_channel_with_id' => $word('title_edit_channel_with_id', 'Edit Channel #{id}'),
            'title_create_search_keyword' => $word('title_create_search_keyword', 'Create Search Keyword'),
            'title_edit_search_keyword_with_id' => $word('title_edit_search_keyword_with_id', 'Edit Search Keyword #{id}'),
            'title_create_post' => $word('title_create_post', 'Create Post'),
            'title_edit_post_with_id' => $word('title_edit_post_with_id', 'Edit Post #{id}'),
            'title_create_page' => $word('title_create_page', 'Create Page'),
            'title_edit_page_with_id' => $word('title_edit_page_with_id', 'Edit Page #{id}'),
            'title_create_tag' => $word('title_create_tag', 'Create Tag'),
            'title_edit_tag_with_id' => $word('title_edit_tag_with_id', 'Edit Tag #{id}'),
            'title_create_advertisement' => $word('title_create_advertisement', 'Create Advertisement'),
            'title_edit_advertisement_with_id' => $word('title_edit_advertisement_with_id', 'Edit Advertisement #{id}'),
            'title_create_tag_type' => $word('title_create_tag_type', 'Create Tag Type'),
            'title_bulk_create_tags' => $word('title_bulk_create_tags', 'Bulk Create Tags'),
            'subtitle_permissions_page' => $word('subtitle_permissions_page', 'Dedicated permission dashboard with bulk role assignment'),
            'subtitle_users_page' => $word('subtitle_users_page', 'Dedicated user management module (form-items style)'),
            'subtitle_create_user' => $word('subtitle_create_user', 'Create user via backend bridge endpoint'),
            'subtitle_edit_user' => $word('subtitle_edit_user', 'Edit user via backend bridge endpoint'),
            'subtitle_menus_page' => $word('subtitle_menus_page', 'Dedicated menu management with clone and builder entry'),
            'subtitle_edit_menu' => $word('subtitle_edit_menu', 'Menu builder and source search'),
            'title_permission_management' => $word('title_permission_management', 'Permission management'),
            'title_manage_users' => $word('title_manage_users', 'Manage users'),
            'title_menu_management' => $word('title_menu_management', 'Menu management'),
            'title_bulk_role_actions' => $word('title_bulk_role_actions', 'Bulk Role Actions'),
            'subtitle_bulk_role_actions' => $word('subtitle_bulk_role_actions', 'Assign / Remove roles for selected permissions'),
            'subtitle_clone_existing_menu' => $word('subtitle_clone_existing_menu', 'Clone existing menu'),

            // Common API/network feedback
            'error_load_roles' => $word('error_load_roles', 'Failed to load roles'),
            'error_network_load_roles' => $word('error_network_load_roles', 'Network error while loading roles'),
            'error_load_users' => $word('error_load_users', 'Failed to load users'),
            'error_network_load_users' => $word('error_network_load_users', 'Network error while loading users'),
            'error_load_user' => $word('error_load_user', 'Failed to load user'),
            'error_load_role_options' => $word('error_load_role_options', 'Failed to load role options'),
            'error_network_load_user_form' => $word('error_network_load_user_form', 'Network error while loading user form'),
            'error_save_user' => $word('error_save_user', 'Failed to save user'),
            'error_network_save_user' => $word('error_network_save_user', 'Network error while saving user'),
            'error_load_menus' => $word('error_load_menus', 'Failed to load menus'),
            'error_network_load_menus' => $word('error_network_load_menus', 'Network error while loading menus'),
            'error_create_menu_failed' => $word('error_create_menu_failed', 'Failed to create menu'),
            'error_network_create_menu' => $word('error_network_create_menu', 'Network error while creating menu'),
            'error_clone_menu_failed' => $word('error_clone_menu_failed', 'Failed to clone menu'),
            'error_network_clone_menu' => $word('error_network_clone_menu', 'Network error while cloning menu'),
            'error_load_menu' => $word('error_load_menu', 'Failed to load menu'),
            'error_menu_payload_invalid' => $word('error_menu_payload_invalid', 'Menu payload is invalid'),
            'error_network_load_menu' => $word('error_network_load_menu', 'Network error while loading menu'),
            'error_search_source_items_failed' => $word('error_search_source_items_failed', 'Failed to search source items'),
            'error_network_search_source_items' => $word('error_network_search_source_items', 'Network error while searching source items'),
            'error_save_menu_failed' => $word('error_save_menu_failed', 'Failed to save menu'),
            'error_network_update_menu' => $word('error_network_update_menu', 'Network error while updating menu'),
            'error_network_load_permissions' => $word('error_network_load_permissions', 'Network error while loading permissions'),
            'error_permission_bulk_operation_failed' => $word('error_permission_bulk_operation_failed', 'Permission bulk operation failed'),
            'error_network_sync_roles' => $word('error_network_sync_roles', 'Network error while syncing roles'),
            'error_delete_permission_failed' => $word('error_delete_permission_failed', 'Delete permission failed'),
            'error_network_delete_permission' => $word('error_network_delete_permission', 'Network error while deleting permission'),
            'error_fetch_resource_list' => $word('error_fetch_resource_list', 'Failed to fetch resource list'),
            'error_network_load_resource' => $word('error_network_load_resource', 'Network error while loading resource'),
            'error_create_failed' => $word('error_create_failed', 'Create failed'),
            'error_invalid_json_create' => $word('error_invalid_json_create', 'Invalid JSON payload for create'),
            'error_invalid_json_payload' => $word('error_invalid_json_payload', 'Invalid JSON payload'),
            'error_provide_update_id' => $word('error_provide_update_id', 'Please provide ID for update'),
            'error_provide_delete_id' => $word('error_provide_delete_id', 'Please provide ID for delete'),
            'error_update_failed' => $word('error_update_failed', 'Update failed'),
            'error_delete_failed' => $word('error_delete_failed', 'Delete failed'),
            'error_bulk_delete_failed' => $word('error_bulk_delete_failed', 'Bulk delete failed'),
            'error_network_delete_record' => $word('error_network_delete_record', 'Network error while deleting record'),
            'error_network_bulk_delete_records' => $word('error_network_bulk_delete_records', 'Network error while bulk deleting records'),
            'error_unable_load_record' => $word('error_unable_load_record', 'Unable to load record. You can still edit JSON manually.'),
            'error_network_load_model' => $word('error_network_load_model', 'Network error while loading model'),
            'error_fallback_update_failed' => $word('error_fallback_update_failed', 'Fallback update failed at column'),
            'error_delete_role' => $word('error_delete_role', 'Delete role failed'),
            'error_network_delete_role' => $word('error_network_delete_role', 'Network error while deleting role'),
            'error_load_permissions' => $word('error_load_permissions', 'Failed to load permissions'),
            'error_load_role' => $word('error_load_role', 'Failed to load role'),
            'error_network_load_role_form' => $word('error_network_load_role_form', 'Network error while loading role form'),
            'error_save_role' => $word('error_save_role', 'Failed to save role'),
            'error_network_save_role' => $word('error_network_save_role', 'Network error while saving role'),
            'success_role_deleted' => $word('success_role_deleted', 'Role deleted.'),
            'success_role_created' => $word('success_role_created', 'Role created.'),
            'success_role_updated' => $word('success_role_updated', 'Role updated.'),
            'success_user_created' => $word('success_user_created', 'User created.'),
            'success_user_updated' => $word('success_user_updated', 'User updated.'),
            'success_menu_created' => $word('success_menu_created', 'Menu created.'),
            'success_menu_cloned' => $word('success_menu_cloned', 'Menu cloned.'),
            'success_assigned_selected_roles' => $word('success_assigned_selected_roles', 'Assigned selected roles.'),
            'success_removed_selected_roles' => $word('success_removed_selected_roles', 'Removed selected roles.'),
            'success_permission_deleted' => $word('success_permission_deleted', 'Permission deleted.'),
            'success_create' => $word('success_create', 'Create success.'),
            'success_update' => $word('success_update', 'Update success.'),
            'success_delete' => $word('success_delete', 'Delete success.'),
            'success_update_fallback_mode' => $word('success_update_fallback_mode', 'Update success (fallback mode).'),
            'loading_model' => $word('loading_model', 'Loading model...'),
            'loading_role_form' => $word('loading_role_form', 'Loading role form...'),
            'loading_user_form' => $word('loading_user_form', 'Loading user form...'),
            'loading_menu' => $word('loading_menu', 'Loading menu...'),

            // Misc labels/placeholders for forms and menus
            'word_username' => $word('username', 'Username'),
            'word_email' => $word('email', 'Email'),
            'word_first_name' => $word('first_name', 'First Name'),
            'word_last_name' => $word('last_name', 'Last Name'),
            'word_nickname' => $word('nickname', 'Nickname'),
            'word_security' => $word('security', 'Security'),
            'word_password_confirmation' => $word('password_confirmation', 'Password Confirmation'),
            'word_items' => $word('items', 'Items'),
            'word_keyword' => $word('keyword', 'keyword'),
            'word_item' => $word('item', 'Item'),
            'word_untitled' => $word('untitled', '(untitled)'),
            'option_all_roles' => $word('all_roles', 'All roles'),
            'button_new_user' => $word('new_user', 'New User'),
            'button_create_menu' => $word('create_menu', 'Create Menu'),
            'button_clone_menu' => $word('clone_menu', 'Clone Menu'),
            'button_edit_builder' => $word('edit_builder', 'Edit Builder'),
            'button_bulk_assign_roles' => $word('bulk_assign_roles', 'Bulk Assign Roles'),
            'button_bulk_remove_roles' => $word('bulk_remove_roles', 'Bulk Remove Roles'),
            'placeholder_search_permission_name' => $word('search_permission_name', 'Search permission name'),
            'placeholder_search_username_email' => $word('search_username_email', 'Search username / email'),
            'placeholder_new_menu_name' => $word('new_menu_name', 'New menu name'),
            'placeholder_source_key_example' => $word('source_key_example', 'source_key (ex: post, page, tag)'),
            'placeholder_link_label' => $word('link_label', 'Link label'),
            'placeholder_link_url' => $word('link_url', 'https://...'),
            'label_menu_json_advanced' => $word('menu_json_advanced', 'Menu JSON (advanced)'),
            'text_no_items_yet' => $word('no_items_yet', 'No items yet.'),
        ];
    }

    protected function normalizeLocale(string $locale): string
    {
        $locale = trim(str_replace('-', '_', $locale));
        return $locale === '' ? 'en' : $locale;
    }

    protected function buildLocaleOptions(array $supported): array
    {
        $locales = [];
        foreach ($supported as $code => $meta) {
            $normalized = $this->normalizeLocale((string) $code);
            $locales[] = [
                'code' => $normalized,
                'native' => (string) ($meta['native'] ?? $normalized),
                'name' => (string) ($meta['name'] ?? $normalized),
                'regional' => (string) ($meta['regional'] ?? ''),
            ];
        }

        return $locales;
    }

    protected function fetchGroupTranslations(
        string $namespace,
        string $group,
        string $locale,
        string $fallback
    ): array {
        $groupKey = "{$namespace}::{$group}";
        $fallbackTranslations = Lang::get($groupKey, [], $fallback);
        $activeTranslations = Lang::get($groupKey, [], $locale);

        if (!is_array($fallbackTranslations)) {
            $fallbackTranslations = [];
        }

        if (!is_array($activeTranslations)) {
            $activeTranslations = [];
        }

        return array_merge($fallbackTranslations, $activeTranslations);
    }

    protected function resolveTranslationValue(
        string $namespace,
        string $group,
        string $key,
        string $locale,
        string $fallback
    ): ?string {
        $dotKey = "{$namespace}::{$group}.{$key}";
        $active = Lang::get($dotKey, [], $locale);

        if (is_string($active) && $active !== $dotKey) {
            return $active;
        }

        $fallbackValue = Lang::get($dotKey, [], $fallback);
        if (is_string($fallbackValue) && $fallbackValue !== $dotKey) {
            return $fallbackValue;
        }

        return null;
    }
}

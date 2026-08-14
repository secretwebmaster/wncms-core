<?php

namespace Wncms\Api\V2\Risk;

use InvalidArgumentException;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Http\Controllers\Api\V2\Backend\ResourceController;

final class LegacyOperationDescriptorRegistry
{
    private const ACTION_OVERRIDES = [
        'pages.bulk_delete', 'permissions.bulk_delete', 'clicks.destroy', 'clicks.bulk_delete',
    ];

    private const READ_ACTIONS = [
        'menus.get_menu_item', 'menus.search_source_items', 'pages.builder.load', 'pages.templates',
        'pages.widget', 'posts.meta', 'posts.translations', 'tags.create_type', 'tags.bulk_create',
        'tags.get_languages', 'clicks.summary', 'comments.users',
    ];

    private const CREDENTIAL_ACTIONS = [
        'users.account.api.update', 'users.account.email.update', 'users.account.password.update',
    ];

    private const DATABASE_ACTIONS = [];

    private const EXTERNAL_ACTIONS = [
        'cache.flush', 'cache.flush.tag', 'cache.clear', 'cache.clear.tag', 'dashboard.switch_website',
        'install_default_theme', 'rerun_core_update', 'menus.edit_menu_item', 'menus.clone',
        'models.update', 'models.bulk_delete', 'models.bulk_force_delete', 'pages.builder.save',
        'pages.create_theme_pages', 'pages.bulk_delete', 'packages.check', 'packages.activate',
        'packages.deactivate', 'plugins.upload', 'plugins.upgrade', 'plugins.activate_raw',
        'plugins.activate', 'plugins.deactivate', 'plugins.delete', 'posts.restore', 'posts.bulk_delete',
        'posts.delete_post', 'posts.bulk_sync_tags', 'posts.generate_demo_posts', 'posts.bulk_clone',
        'settings.update', 'settings.smtp_test', 'settings.google_test', 'settings.quick.add',
        'settings.quick.remove', 'tags.store_type', 'tags.bulk_store', 'tags.import_csv',
        'tags.keywords.update', 'tags.bulk_set_parent', 'themes.upload', 'themes.delete',
        'uploads.image', 'updates.check', 'records.bulk_delete', 'records.destroy', 'clicks.destroy',
        'clicks.bulk_delete', 'users.manage.store', 'users.manage.update', 'users.manage.destroy',
        'users.manage.bulk_delete', 'users.account.profile.update', 'websites.theme.options.update',
        'websites.theme.clone', 'websites.theme.import_default_option', 'permissions.bulk_assign_roles',
        'permissions.bulk_remove_roles', 'permissions.bulk_delete', 'advertisements.manage.update',
        'advertisements.manage.destroy', 'comments.update_post', 'comments.delete_post', 'links.bulk_update',
        'links.bulk_sync_tags',
    ];

    private const CRITICAL_ACTIONS = [
        'rerun_core_update', 'models.bulk_delete', 'models.bulk_force_delete', 'pages.bulk_delete',
        'permissions.bulk_delete', 'plugins.delete', 'posts.bulk_delete', 'records.bulk_delete',
        'clicks.bulk_delete', 'users.manage.destroy', 'users.manage.bulk_delete', 'themes.delete',
    ];

    private const SERVICE_RESOURCE_ALLOWLIST = [
        'advertisements', 'channels', 'comments', 'clicks', 'links', 'menus', 'pages', 'parameters',
        'posts', 'search_keywords', 'tags', 'websites',
    ];

    private const SERVICE_ACTION_ALLOWLIST = [
        'advertisements.manage.update', 'advertisements.manage.destroy', 'comments.update_post',
        'comments.delete_post', 'links.bulk_update', 'links.bulk_sync_tags',
    ];

    private const ACTION_MODEL_KEYS = [
        'permissions.bulk_assign_roles' => ['permission', 'role'],
        'permissions.bulk_remove_roles' => ['permission', 'role'],
        'permissions.bulk_delete' => ['permission'],
        'advertisements.manage.update' => ['advertisement'],
        'advertisements.manage.destroy' => ['advertisement'],
        'comments.update_post' => ['comment'],
        'comments.delete_post' => ['comment'],
        'links.bulk_update' => ['link'],
        'links.bulk_sync_tags' => ['link', 'tag'],
        'models.update' => [],
        'models.bulk_delete' => [],
        'models.bulk_force_delete' => [],
        'posts.bulk_sync_tags' => ['post', 'tag'],
        'posts.bulk_clone' => ['post'],
        'users.account.api.update' => ['user'],
        'users.account.email.update' => ['user'],
        'users.account.password.update' => ['user', 'api_session'],
    ];

    /**
     * Materialize and validate every configured legacy operation descriptor.
     *
     * @param  array<string, array<string, mixed>>  $resources
     * @param  array<int, array<string, mixed>>  $actions
     * @return array<string, \Wncms\Api\V2\Risk\LegacyOperationDescriptor>
     */
    public function configured(array $resources, array $actions): array
    {
        $this->validateCollisions($resources, $actions);
        $descriptors = [];
        foreach ($resources as $resource => $config) {
            $enabled = $config['enabled_actions'] ?? ['index', 'show', 'store', 'update', 'destroy', 'bulk_delete'];
            foreach ($enabled as $action) {
                if ($action === 'bulk_delete' && ($config['enable_bulk_delete'] ?? true) !== true) {
                    continue;
                }
                $descriptor = $this->resource((string) $resource, (string) $action, $config);
                $descriptors[$descriptor->operationId] = $descriptor;
            }
        }
        foreach ($actions as $action) {
            $descriptor = $this->action($action);
            $descriptors[$descriptor->operationId] = $descriptor;
        }

        return $descriptors;
    }

    /**
     * Reject unapproved resource and bridge operation collisions.
     *
     * @param  array<string, array<string, mixed>>  $resources
     * @param  array<int, array<string, mixed>>  $actions
     */
    public function validateCollisions(array $resources, array $actions): void
    {
        $resourceIds = [];
        foreach ($resources as $resource => $config) {
            foreach ($config['enabled_actions'] ?? ['index', 'show', 'store', 'update', 'destroy', 'bulk_delete'] as $action) {
                if ($action !== 'bulk_delete' || ($config['enable_bulk_delete'] ?? true) === true) {
                    $resourceIds[] = "backend.{$resource}.{$action}";
                }
            }
        }
        foreach ($actions as $action) {
            $name = (string) ($action['name'] ?? '');
            if (in_array('backend.'.$name, $resourceIds, true) && ! in_array($name, self::ACTION_OVERRIDES, true)) {
                throw new InvalidArgumentException("Legacy operation [backend.{$name}] has an unapproved resource/bridge collision.");
            }
        }
    }

    /**
     * Resolve one configured resource descriptor.
     *
     * @param  array<string, mixed>  $config
     */
    public function resource(string $resource, string $action, array $config): LegacyOperationDescriptor
    {
        if (! in_array($action, ['index', 'show', 'store', 'update', 'destroy', 'bulk_delete'], true)) {
            throw new InvalidArgumentException("Unsupported legacy resource action [{$resource}.{$action}].");
        }
        $operationId = "backend.{$resource}.{$action}";
        $read = in_array($action, ['index', 'show'], true);
        $credential = in_array($resource, ['permissions', 'roles', 'users'], true) && ! $read;
        $risk = match ($action) {
            'bulk_delete' => 'critical',
            'update', 'destroy' => 'high',
            'store' => 'sensitive',
            default => 'normal',
        };
        $controller = $config['controller'] ?? ResourceController::class;
        $database = $controller === ResourceController::class && ! in_array($resource, ['permissions', 'roles'], true);
        $modelKeys = [(string) ($config['model_key'] ?? '')];
        $modelKeys = $database ? array_values(array_filter(array_unique($modelKeys))) : [];
        $relationshipBoundaries = $database && ! $read ? ['websites'] : [];
        if ($relationshipBoundaries !== []) {
            $modelKeys[] = 'website';
        }
        $plan = $database && in_array($risk, ['high', 'critical'], true);
        $ability = $resource.'.'.($read ? 'read' : 'write');
        $dataRisk = $read ? 'read' : (in_array($action, ['destroy', 'bulk_delete'], true) ? 'destructive' : 'write');

        return new LegacyOperationDescriptor(
            $operationId,
            $ability,
            $dataRisk,
            $risk,
            ($credential || $risk === 'critical') ? [ApiCredential::TYPE_INTERACTIVE_ACCESS] : $this->resourceCredentials($resource),
            $credential,
            $credential ? ["{$resource}.{$action}"] : [],
            $plan,
            $modelKeys,
            [],
            $read ? 'read' : ($database ? 'database' : 'external'),
            'resource',
            $resource === 'roles' ? 'role' : ($action === 'bulk_delete' ? 'bulk_ids' : ($action === 'store' ? 'create' : 'route_id')),
            $plan,
            $relationshipBoundaries,
        );
    }

    /**
     * Resolve one explicitly classified bridge descriptor.
     *
     * @param  array<string, mixed>  $action
     */
    public function action(array $action): LegacyOperationDescriptor
    {
        $name = trim((string) ($action['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Legacy bridge descriptor requires an operation name.');
        }
        $groups = array_values(array_filter([
            in_array($name, self::READ_ACTIONS, true) ? 'read' : null,
            in_array($name, self::CREDENTIAL_ACTIONS, true) ? 'credential' : null,
            in_array($name, self::DATABASE_ACTIONS, true) ? 'database' : null,
            in_array($name, self::EXTERNAL_ACTIONS, true) ? 'external' : null,
        ]));
        if (count($groups) !== 1) {
            throw new InvalidArgumentException("Legacy bridge operation [{$name}] must have exactly one explicit descriptor classification.");
        }
        $kind = $groups[0];
        $read = $kind === 'read';
        $credential = $kind === 'credential';
        $database = $kind === 'database';
        $risk = $read ? 'normal' : ($credential || in_array($name, self::CRITICAL_ACTIONS, true) ? 'critical' : 'high');
        $plan = $database && in_array($risk, ['high', 'critical'], true);
        $serviceAllowed = in_array($name, self::READ_ACTIONS, true) || in_array($name, self::SERVICE_ACTION_ALLOWLIST, true);
        $domain = explode('.', $name, 2)[0];
        $ability = $domain.'.'.($read ? 'read' : 'write');
        $dataRisk = $read ? 'read' : (in_array($risk, ['critical'], true) ? 'destructive' : 'write');

        return new LegacyOperationDescriptor(
            'backend.'.$name,
            $ability,
            $dataRisk,
            $risk,
            $credential || ! $serviceAllowed || $risk === 'critical'
                ? [ApiCredential::TYPE_INTERACTIVE_ACCESS]
                : [ApiCredential::TYPE_INTERACTIVE_ACCESS, ApiCredential::TYPE_SERVICE_TOKEN, ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN],
            $credential,
            $credential ? [$name] : [],
            $plan,
            self::ACTION_MODEL_KEYS[$name] ?? [],
            [],
            $read ? 'read' : ($database ? 'database' : 'external'),
            str_starts_with($name, 'models.') ? 'dynamic_model' : 'bridge',
            $this->targetResolver($name),
            $plan,
        );
    }

    /**
     * Return the explicit credential allowlist for one resource.
     *
     * @return array<int, string>
     */
    private function resourceCredentials(string $resource): array
    {
        return in_array($resource, self::SERVICE_RESOURCE_ALLOWLIST, true)
            ? [ApiCredential::TYPE_INTERACTIVE_ACCESS, ApiCredential::TYPE_SERVICE_TOKEN, ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN]
            : [ApiCredential::TYPE_INTERACTIVE_ACCESS];
    }

    /**
     * Return the maintained target resolver name for one bridge operation.
     */
    private function targetResolver(string $name): string
    {
        return match ($name) {
            'models.update', 'models.bulk_delete', 'models.bulk_force_delete' => 'dynamic_model_ids',
            'permissions.bulk_assign_roles', 'permissions.bulk_remove_roles' => 'role_permission_ids',
            'permissions.bulk_delete' => 'permission_ids',
            'posts.bulk_sync_tags', 'posts.bulk_clone', 'posts.bulk_delete' => 'post_ids',
            'links.bulk_update', 'links.bulk_sync_tags' => 'link_ids',
            'users.account.api.update', 'users.account.email.update', 'users.account.password.update', 'users.account.profile.update' => 'actor',
            default => str_contains((string) ($name), 'bulk_') ? 'bulk_ids' : 'route_id',
        };
    }
}

<?php

namespace Wncms\Tests\Unit\Api\V2;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wncms\Api\V2\Risk\LegacyOperationDescriptorRegistry;

class LegacyOperationDescriptorRegistryTest extends TestCase
{
    /**
     * Verify every configured legacy operation has one explicit security descriptor.
     */
    public function test_every_configured_operation_has_a_complete_explicit_descriptor(): void
    {
        $config = require dirname(__DIR__, 4).'/config/wncms-backend-api-v2.php';
        $registry = new LegacyOperationDescriptorRegistry;

        $descriptors = $registry->configured($config['resources'], $config['actions']);

        $operationIds = array_map(static fn (array $action): string => 'backend.'.$action['name'], $config['actions']);
        foreach ($config['resources'] as $name => $resource) {
            $actions = $resource['enabled_actions'] ?? ['index', 'show', 'store', 'update', 'destroy', 'bulk_delete'];
            foreach ($actions as $action) {
                if ($action !== 'bulk_delete' || ($resource['enable_bulk_delete'] ?? true) === true) {
                    $operationIds[] = "backend.{$name}.{$action}";
                }
            }
        }
        $this->assertCount(count($operationIds) - 4, $descriptors);
        foreach ($descriptors as $operationId => $descriptor) {
            $this->assertSame($operationId, $descriptor->operationId);
            $this->assertNotSame([], $descriptor->acceptedCredentialTypes);
            $this->assertContains($descriptor->sideEffectKind, ['database', 'transactional_outbox', 'external', 'read']);
            $this->assertNotSame('', $descriptor->canonicalizer);
            $this->assertNotSame('', $descriptor->targetResolver);
            $this->assertNotSame('', $descriptor->ability);
            $this->assertContains($descriptor->dataRisk, ['read', 'write', 'destructive']);
        }
    }

    /**
     * Verify credential mutations and service-safe content mutations have explicit allowlists.
     */
    public function test_credential_and_service_token_boundaries_are_explicit(): void
    {
        $registry = new LegacyOperationDescriptorRegistry;

        $password = $registry->action($this->action('users.account.password.update'));
        $this->assertSame(['interactive_access'], $password->acceptedCredentialTypes);
        $this->assertTrue($password->requiresStepUp);
        $this->assertSame(['users.account.password.update'], $password->stepUpPurposes);

        $cache = $registry->action($this->action('cache.flush'));
        $this->assertSame(['interactive_access'], $cache->acceptedCredentialTypes);
        $this->assertSame('external', $cache->sideEffectKind);
        $this->assertFalse($cache->actionPlanEligible);

        $channel = $registry->resource('channels', 'update', [
            'model_key' => 'channel',
            'permissions' => ['update' => 'channel_edit'],
        ]);
        $this->assertSame(['interactive_access', 'service_token', 'legacy_personal_access_token'], $channel->acceptedCredentialTypes);
        $this->assertSame(['channel', 'website'], $channel->domainModelKeys);
        $this->assertSame(['websites'], $channel->relationshipBoundaries);
    }

    /**
     * Verify semantic read/write declarations do not follow the transport method.
     */
    public function test_bridge_ability_and_data_risk_are_explicit(): void
    {
        $registry = new LegacyOperationDescriptorRegistry;

        $postRead = $registry->action($this->action('menus.get_menu_item'));
        $this->assertSame('menus.read', $postRead->ability);
        $this->assertSame('read', $postRead->dataRisk);

        $getExternal = $registry->action($this->action('settings.google_test'));
        $this->assertSame('settings.write', $getExternal->ability);
        $this->assertSame('write', $getExternal->dataRisk);
    }

    /**
     * Verify only maintained collision overrides may replace resource descriptors.
     */
    public function test_unapproved_operation_collision_fails_closed(): void
    {
        $registry = new LegacyOperationDescriptorRegistry;

        $this->expectException(InvalidArgumentException::class);
        $registry->configured([
            'menus' => [
                'model_key' => 'menu',
                'enabled_actions' => ['update'],
                'permissions' => ['update' => 'menu_edit'],
            ],
        ], [[
            'name' => 'menus.update',
            'method' => 'post',
            'permission' => 'menu_edit',
        ]]);
    }

    /**
     * Verify every production collision has an explicit audited bridge override.
     */
    public function test_production_collisions_are_explicit_overrides(): void
    {
        $config = require dirname(__DIR__, 4).'/config/wncms-backend-api-v2.php';
        $descriptors = (new LegacyOperationDescriptorRegistry)->configured($config['resources'], $config['actions']);

        $collisions = array_keys(array_filter(
            $descriptors,
            static fn ($descriptor): bool => in_array($descriptor->operationId, [
                'backend.clicks.bulk_delete', 'backend.clicks.destroy', 'backend.pages.bulk_delete', 'backend.permissions.bulk_delete',
            ], true),
        ));
        sort($collisions);

        $this->assertSame([
            'backend.clicks.bulk_delete',
            'backend.clicks.destroy',
            'backend.pages.bulk_delete',
            'backend.permissions.bulk_delete',
        ], $collisions);
    }

    /**
     * Find one real configured bridge action.
     *
     * @return array<string, mixed>
     */
    private function action(string $name): array
    {
        $config = require dirname(__DIR__, 4).'/config/wncms-backend-api-v2.php';

        return array_values(array_filter($config['actions'], static fn (array $action): bool => $action['name'] === $name))[0];
    }
}

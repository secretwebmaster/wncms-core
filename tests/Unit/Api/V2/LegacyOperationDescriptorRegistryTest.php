<?php

namespace Wncms\Tests\Unit\Api\V2;

use PHPUnit\Framework\TestCase;
use Wncms\Api\V2\Risk\LegacyOperationDescriptorRegistry;

class LegacyOperationDescriptorRegistryTest extends TestCase
{
    /**
     * Verify every configured legacy operation has one explicit security descriptor.
     *
     * @return void
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
        $this->assertCount(count(array_unique($operationIds)), $descriptors);
        foreach ($descriptors as $operationId => $descriptor) {
            $this->assertSame($operationId, $descriptor->operationId);
            $this->assertNotSame([], $descriptor->acceptedCredentialTypes);
            $this->assertContains($descriptor->sideEffectKind, ['database', 'transactional_outbox', 'external', 'read']);
            $this->assertNotSame('', $descriptor->canonicalizer);
            $this->assertNotSame('', $descriptor->targetResolver);
        }
    }

    /**
     * Verify credential mutations and service-safe content mutations have explicit allowlists.
     *
     * @return void
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
        $this->assertSame(['interactive_access', 'service_token'], $channel->acceptedCredentialTypes);
        $this->assertSame(['channel'], $channel->domainModelKeys);
    }

    /**
     * Find one real configured bridge action.
     *
     * @param  string  $name
     * @return array<string, mixed>
     */
    private function action(string $name): array
    {
        $config = require dirname(__DIR__, 4).'/config/wncms-backend-api-v2.php';

        return array_values(array_filter($config['actions'], static fn (array $action): bool => $action['name'] === $name))[0];
    }
}

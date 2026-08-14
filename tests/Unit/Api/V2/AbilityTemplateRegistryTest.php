<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Validation\ValidationException;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\ModelPermissionResolver;
use Wncms\Auth\Api\V2\AbilityTemplateRegistry;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class AbilityTemplateRegistryTest extends TestCase
{
    public function test_templates_are_registry_backed_and_never_delegate_credential_operations(): void
    {
        $templates = $this->templates([
            $this->operation('backend.posts.index', 'posts', 'GET', 'read', 'posts.read'),
            $this->operation('backend.posts.store', 'posts', 'POST', 'write', 'posts.write'),
            $this->operation('backend.websites.update', 'websites', 'PATCH', 'write', 'websites.write'),
            $this->operation('backend.plugins.run', 'plugins', 'POST', 'external', 'plugins.run'),
            $this->operation('backend.security_events.index', 'security_events', 'GET', 'read', 'security_events.read'),
            $this->operation('backend.auth.password.update', 'auth', 'PATCH', 'write', 'credentials.write'),
        ]);
        $actor = new User();

        $this->assertSame(['posts.read'], $templates->resolveGrant($actor, 'read_only', [], []));
        $this->assertSame(['posts.read', 'posts.write'], $templates->resolveGrant($actor, 'content_editor', [], []));
        $this->assertSame(['posts.read', 'posts.write', 'websites.write'], $templates->resolveGrant($actor, 'site_manager', [], []));
        $this->assertSame(
            ['plugins.run', 'posts.read', 'posts.write', 'security_events.read', 'websites.write'],
            $templates->resolveGrant($actor, 'full_admin', [], []),
        );
    }

    public function test_additions_and_removals_stay_within_the_actor_grantable_catalog(): void
    {
        $templates = $this->templates([
            $this->operation('backend.posts.index', 'posts', 'GET', 'read', 'posts.read'),
            $this->operation('backend.posts.store', 'posts', 'POST', 'write', 'posts.write'),
        ]);
        $actor = new User();

        $this->assertSame(
            ['posts.write'],
            $templates->resolveGrant($actor, 'read_only', ['posts.write'], ['posts.read']),
        );

        $this->expectException(ValidationException::class);
        $templates->resolveGrant($actor, 'read_only', ['unknown.write'], []);
    }

    /** @param array<int, ApiOperationContract> $operations */
    private function templates(array $operations): AbilityTemplateRegistry
    {
        $registry = new ApiContractRegistry();
        foreach ($operations as $operation) {
            if (! isset($registry->domains()[$operation->domain])) {
                $registry->registerDomain(new ApiDomainContract($operation->domain, ucfirst($operation->domain)));
            }
            $registry->registerOperation($operation);
        }

        return new AbilityTemplateRegistry($registry, new ModelPermissionResolver());
    }

    private function operation(
        string $id,
        string $domain,
        string $method,
        string $sideEffect,
        string $ability,
    ): ApiOperationContract {
        return new ApiOperationContract(
            id: $id,
            domain: $domain,
            surface: 'backend',
            method: $method,
            path: '/api/v2/backend/test',
            routeName: str_replace('backend.', 'api.v2.backend.', $id),
            permission: null,
            ability: $ability,
            websiteScoped: true,
            risk: $method === 'GET' ? 'read' : 'write',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
            sideEffectKind: $sideEffect,
        );
    }
}

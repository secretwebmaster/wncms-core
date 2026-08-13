<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Risk\RiskContextException;
use Wncms\Api\V2\Risk\TargetOperationAuthorizer;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class TargetOperationAuthorizerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_role_permission_snapshot_ignores_cached_grants_after_authoritative_revoke(): void
    {
        $actor = User::factory()->create();
        $permission = Permission::findOrCreate('channel_edit', 'web');
        $role = Role::findOrCreate('snapshot-role-'.uniqid(), 'web');
        $role->givePermissionTo($permission);
        $actor->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $actor->checkPermissionTo('channel_edit');
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'authority-role', 'authority-session', ['channels.write'], []);
        $authorizer = app(TargetOperationAuthorizer::class);

        $authorizer->authorize($context, $this->operation());
        DB::table(config('permission.table_names.role_has_permissions'))
            ->where('role_id', $role->getKey())
            ->where('permission_id', $permission->getKey())
            ->delete();

        try {
            $authorizer->authorize($context, $this->operation());
            $this->fail('Expected authoritative role permission denial.');
        } catch (RiskContextException $exception) {
            $this->assertSame('authorization.permission_denied', $exception->errorCode);
            $this->assertSame(403, $exception->httpStatus);
        }
    }

    public function test_fresh_wildcard_direct_permission_is_authorized_and_revocation_is_immediate(): void
    {
        config(['permission.enable_wildcard_permission' => true]);
        $actor = User::factory()->create();
        $wildcard = Permission::findOrCreate('channel.*', 'web');
        $actor->givePermissionTo($wildcard);
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'authority-wildcard-direct', 'authority-session', ['channels.write'], []);
        $authorizer = app(TargetOperationAuthorizer::class);

        $authorizer->authorize($context, $this->operation('channel.edit'));
        DB::table(config('permission.table_names.model_has_permissions'))
            ->where('model_id', $actor->getKey())
            ->where('permission_id', $wildcard->getKey())
            ->delete();

        $this->expectPermissionDenied(fn () => $authorizer->authorize($context, $this->operation('channel.edit')));
    }

    public function test_fresh_wildcard_role_permission_is_authorized_and_revocation_is_immediate(): void
    {
        config(['permission.enable_wildcard_permission' => true]);
        $actor = User::factory()->create();
        $wildcard = Permission::findOrCreate('channel.edit,delete', 'web');
        $role = Role::findOrCreate('wildcard-role-'.uniqid(), 'web');
        $role->givePermissionTo($wildcard);
        $actor->assignRole($role);
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'authority-wildcard-role', 'authority-session', ['channels.write'], []);
        $authorizer = app(TargetOperationAuthorizer::class);

        $authorizer->authorize($context, $this->operation('channel.edit'));
        DB::table(config('permission.table_names.role_has_permissions'))
            ->where('role_id', $role->getKey())
            ->where('permission_id', $wildcard->getKey())
            ->delete();

        $this->expectPermissionDenied(fn () => $authorizer->authorize($context, $this->operation('channel.edit')));
    }

    public function test_fresh_wildcard_permission_never_crosses_guard_names(): void
    {
        config(['permission.enable_wildcard_permission' => true]);
        $actor = User::factory()->create();
        $wildcard = Permission::findOrCreate('channel.*', 'api');
        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => $wildcard->getKey(),
            'model_type' => $actor::class,
            'model_id' => $actor->getKey(),
        ]);
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'authority-wildcard-guard', 'authority-session', ['channels.write'], []);

        $this->expectPermissionDenied(fn () => app(TargetOperationAuthorizer::class)->authorize($context, $this->operation('channel.edit')));
    }

    private function operation(string $permission = 'channel_edit'): ApiOperationContract
    {
        return new ApiOperationContract(
            id: 'backend.channels.update', domain: 'channels', surface: 'backend', method: 'PATCH',
            path: '/api/v2/backend/channels/{id}', routeName: 'api.v2.backend.channels.update',
            permission: $permission, ability: 'channels.write', websiteScoped: true,
            risk: 'write', implementation: 'legacy_resource', request: ApiSchema::object(), response: ApiSchema::object(),
            securityRisk: 'high', actionPlanEligible: true, domainModelKeys: ['channel'],
        );
    }

    private function expectPermissionDenied(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected authoritative permission denial.');
        } catch (RiskContextException $exception) {
            $this->assertSame('authorization.permission_denied', $exception->errorCode);
            $this->assertSame(403, $exception->httpStatus);
        }
    }
}

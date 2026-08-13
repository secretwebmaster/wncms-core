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

    private function operation(): ApiOperationContract
    {
        return new ApiOperationContract(
            id: 'backend.channels.update', domain: 'channels', surface: 'backend', method: 'PATCH',
            path: '/api/v2/backend/channels/{id}', routeName: 'api.v2.backend.channels.update',
            permission: 'channel_edit', ability: 'channels.write', websiteScoped: true,
            risk: 'write', implementation: 'legacy_resource', request: ApiSchema::object(), response: ApiSchema::object(),
            securityRisk: 'high', actionPlanEligible: true, domainModelKeys: ['channel'],
        );
    }
}

<?php

namespace Wncms\Api\V2\Risk;

use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Auth\Api\V2\AuthenticationContext;

final class TargetOperationAuthorizer
{
    /**
     * Authorize one operation against the current credential and actor state.
     *
     * @throws \Wncms\Api\V2\Risk\RiskContextException
     */
    public function authorize(AuthenticationContext $context, ApiOperationContract $operation): void
    {
        if (! in_array($context->credentialType(), $operation->acceptedCredentialTypes, true)) {
            throw new RiskContextException('risk.credential_type_denied', 403);
        }
        if ($operation->ability !== null && ! $context->hasAbility($operation->ability) && ! $context->hasAbility('*')) {
            throw new RiskContextException('authorization.ability_denied', 403);
        }
        if ($operation->permission === null) {
            return;
        }
        if ($operation->permissionMode !== 'static') {
            throw new RiskContextException('authorization.permission_denied', 403);
        }
        $actor = $context->actor();
        $freshActor = $context->actorId() !== null
            ? $actor->newQuery()->whereKey($context->actorId())->lockForUpdate()->first()
            : null;
        if ($freshActor === null || ! method_exists($freshActor, 'checkPermissionTo') || ! $freshActor->checkPermissionTo($operation->permission)) {
            throw new RiskContextException('authorization.permission_denied', 403);
        }
    }
}

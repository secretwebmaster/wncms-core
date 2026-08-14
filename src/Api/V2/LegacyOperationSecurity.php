<?php

namespace Wncms\Api\V2;

use InvalidArgumentException;
use Wncms\Api\V2\Risk\LegacyOperationDescriptorRegistry;

final class LegacyOperationSecurity
{
    private const MODEL_PERMISSION_OPERATIONS = [
        'models.update' => ['{model}_edit', 'edit'],
        'models.bulk_delete' => ['{model}_bulk_delete', 'bulk_delete'],
        'models.bulk_force_delete' => ['{model}_bulk_delete', 'bulk_delete'],
    ];

    /**
     * Build the ordered middleware catalog entry for one resource operation.
     *
     * @param  array<string, mixed>  $resourceConfig
     * @return array<int, string>
     */
    public static function resourceMiddleware(string $resource, string $action, array $resourceConfig): array
    {
        return self::resourceRequirements($resource, $action, $resourceConfig)['middleware'];
    }

    /**
     * Resolve the validated security contract for one resource operation.
     *
     * @param  array<string, mixed>  $resourceConfig
     * @return array{ability: string, data_risk: string, permission: string, permission_mode: string, security_risk: string, accepted_credential_types: array<int, string>, requires_step_up: bool, step_up_purposes: array<int, string>, action_plan_eligible: bool, domain_model_keys: array<int, string>, transactional_outbox_model_keys: array<int, string>, side_effect_kind: string, canonicalizer: string, target_resolver: string, relationship_boundaries: array<int, string>, idempotent: bool, middleware: array<int, string>}
     */
    public static function resourceRequirements(string $resource, string $action, array $resourceConfig): array
    {
        $permission = trim((string) ($resourceConfig['permissions'][$action] ?? ''));
        if ($permission === '') {
            throw new InvalidArgumentException("Backend API resource [{$resource}.{$action}] must declare a permission.");
        }
        if (str_contains($permission, '{model}')) {
            throw new InvalidArgumentException("Backend API resource [{$resource}.{$action}] cannot use a model template as a static permission.");
        }

        $descriptor = (new LegacyOperationDescriptorRegistry)->resource($resource, $action, $resourceConfig);

        return [
            'ability' => $descriptor->ability,
            'data_risk' => $descriptor->dataRisk,
            'permission' => $permission,
            'permission_mode' => 'static',
            'security_risk' => $descriptor->securityRisk,
            'accepted_credential_types' => $descriptor->acceptedCredentialTypes,
            'requires_step_up' => $descriptor->requiresStepUp,
            'step_up_purposes' => $descriptor->stepUpPurposes,
            'action_plan_eligible' => $descriptor->actionPlanEligible,
            'domain_model_keys' => $descriptor->domainModelKeys,
            'transactional_outbox_model_keys' => $descriptor->transactionalOutboxModelKeys,
            'side_effect_kind' => $descriptor->sideEffectKind,
            'canonicalizer' => $descriptor->canonicalizer,
            'target_resolver' => $descriptor->targetResolver,
            'relationship_boundaries' => $descriptor->relationshipBoundaries,
            'idempotent' => $descriptor->idempotent,
            'middleware' => self::middleware(
                $descriptor->ability,
                'api_v2_permission:'.$permission,
                $descriptor->idempotent,
                (bool) ($resourceConfig['website_scoped'] ?? true),
            ),
        ];
    }

    /**
     * Build the ordered middleware catalog entry for one bridge operation.
     *
     * @param  array<string, mixed>  $action
     * @return array<int, string>
     */
    public static function actionMiddleware(array $action): array
    {
        return self::actionRequirements($action)['middleware'];
    }

    /**
     * Resolve the validated security contract for one bridge operation.
     *
     * @param  array<string, mixed>  $action
     * @return array{ability: string, data_risk: string, permission: string, permission_mode: string, security_risk: string, accepted_credential_types: array<int, string>, requires_step_up: bool, step_up_purposes: array<int, string>, action_plan_eligible: bool, domain_model_keys: array<int, string>, transactional_outbox_model_keys: array<int, string>, side_effect_kind: string, canonicalizer: string, target_resolver: string, relationship_boundaries: array<int, string>, idempotent: bool, middleware: array<int, string>}
     */
    public static function actionRequirements(array $action): array
    {
        $name = trim((string) ($action['name'] ?? ''));
        $permission = trim((string) ($action['permission'] ?? ''));
        $template = trim((string) ($action['permission_template'] ?? ''));
        if ($name === '' || ($permission === '' && $template === '')) {
            throw new InvalidArgumentException('Backend API bridge operations must declare a name and permission.');
        }

        if ($permission !== '' && $template !== '') {
            throw new InvalidArgumentException("Backend API bridge operation [{$name}] cannot declare two permission modes.");
        }

        if ($permission !== '' && str_contains($permission, '{model}')) {
            throw new InvalidArgumentException("Backend API bridge operation [{$name}] cannot use a model template as a static permission.");
        }

        $modelPermission = self::MODEL_PERMISSION_OPERATIONS[$name] ?? null;
        if ($template !== '' && ($modelPermission === null || $modelPermission[0] !== $template)) {
            throw new InvalidArgumentException("Backend API bridge operation [{$name}] has an unsupported permission template.");
        }

        $permissionIdentity = $template !== '' ? $template : $permission;
        $permissionMiddleware = $template !== ''
            ? 'api_v2_model_permission:'.$modelPermission[1]
            : 'api_v2_permission:'.$permission;

        $descriptor = (new LegacyOperationDescriptorRegistry)->action($action);

        return [
            'ability' => $descriptor->ability,
            'data_risk' => $descriptor->dataRisk,
            'permission' => $permissionIdentity,
            'permission_mode' => $template !== '' ? 'model_template' : 'static',
            'security_risk' => $descriptor->securityRisk,
            'accepted_credential_types' => $descriptor->acceptedCredentialTypes,
            'requires_step_up' => $descriptor->requiresStepUp,
            'step_up_purposes' => $descriptor->stepUpPurposes,
            'action_plan_eligible' => $descriptor->actionPlanEligible,
            'domain_model_keys' => $descriptor->domainModelKeys,
            'transactional_outbox_model_keys' => $descriptor->transactionalOutboxModelKeys,
            'side_effect_kind' => $descriptor->sideEffectKind,
            'canonicalizer' => $descriptor->canonicalizer,
            'target_resolver' => $descriptor->targetResolver,
            'relationship_boundaries' => $descriptor->relationshipBoundaries,
            'idempotent' => $descriptor->idempotent,
            'middleware' => self::middleware(
                $descriptor->ability,
                $permissionMiddleware,
                $descriptor->idempotent,
                (bool) ($action['website_scoped'] ?? true),
            ),
        ];
    }

    /**
     * Return the stable read/write ability for one resource operation.
     */
    public static function resourceAbility(string $resource, string $action): string
    {
        return $resource.'.'.(in_array($action, ['index', 'show'], true) ? 'read' : 'write');
    }

    /**
     * Return the stable read/write ability for one configured bridge operation.
     */
    public static function actionAbility(string $name, string $method): string
    {
        return (new LegacyOperationDescriptorRegistry)->action([
            'name' => $name,
            'method' => $method,
        ])->ability;
    }

    /**
     * Return the mandatory ordered authorization middleware chain.
     *
     * @return array<int, string>
     */
    private static function middleware(string $ability, string $permissionMiddleware, bool $idempotent, bool $websiteScoped): array
    {
        $middleware = [
            'api_v2_ability:'.$ability,
            $permissionMiddleware,
        ];
        if ($websiteScoped) {
            $middleware[] = 'api_v2_website_scope';
        }
        $middleware[] = 'api_v2_risk_context';
        if ($idempotent) {
            $middleware[] = 'api_v2_idempotency';
        }
        $middleware[] = 'api_v2_risk';

        return $middleware;
    }
}

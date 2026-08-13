<?php

namespace Wncms\Tests\Unit\Api\V2;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Wncms\Api\V2\LegacyOperationSecurity;

class LegacyOperationSecurityTest extends TestCase
{
    /**
     * Verify resource read/write abilities and permissions produce literal ordered guards.
     *
     * @param  string  $action
     * @param  string  $ability
     * @param  string  $permission
     * @param  string  $risk
     * @param  bool  $planEligible
     * @return void
     */
    #[DataProvider('resourceRequirementProvider')]
    public function test_resource_requirements_use_stable_literal_mappings(
        string $action,
        string $ability,
        string $permission,
        string $risk,
        bool $planEligible,
    ): void {
        $requirements = LegacyOperationSecurity::resourceRequirements('links', $action, [
            'permissions' => [$action => $permission],
        ]);

        $this->assertSame($ability, $requirements['ability']);
        $this->assertSame($permission, $requirements['permission']);
        $this->assertSame('static', $requirements['permission_mode']);
        $this->assertSame($risk, $requirements['security_risk']);
        $this->assertSame(['interactive_access', 'service_token'], $requirements['accepted_credential_types']);
        $this->assertFalse($requirements['requires_step_up']);
        $this->assertSame([], $requirements['step_up_purposes']);
        $this->assertSame($planEligible, $requirements['action_plan_eligible']);
        $middleware = [
            'api_v2_ability:'.$ability,
            'api_v2_permission:'.$permission,
            'api_v2_website_scope',
            'api_v2_risk_context',
        ];
        if ($planEligible) {
            $middleware[] = 'api_v2_idempotency';
        }
        $middleware[] = 'api_v2_risk';

        $this->assertSame($middleware, $requirements['middleware']);
    }

    /**
     * Provide literal resource security expectations.
     *
     * @return array<string, array{string, string, string, string, bool}>
     */
    public static function resourceRequirementProvider(): array
    {
        return [
            'index reads' => ['index', 'links.read', 'link_index', 'normal', false],
            'show reads' => ['show', 'links.read', 'link_edit', 'normal', false],
            'store writes' => ['store', 'links.write', 'link_create', 'sensitive', false],
            'update writes' => ['update', 'links.write', 'link_edit', 'high', true],
            'destroy writes' => ['destroy', 'links.write', 'link_delete', 'high', true],
            'bulk delete writes' => ['bulk_delete', 'links.write', 'link_bulk_delete', 'critical', true],
        ];
    }

    /**
     * Verify bridge requirements support static and model-target permission declarations.
     *
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $expected
     * @return void
     */
    #[DataProvider('actionRequirementProvider')]
    public function test_action_requirements_use_stable_literal_mappings(array $action, array $expected): void
    {
        $this->assertSame($expected, LegacyOperationSecurity::actionRequirements($action));
    }

    /**
     * Provide literal bridge security expectations.
     *
     * @return array<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function actionRequirementProvider(): array
    {
        return [
            'static read' => [[
                'name' => 'pages.builder.load',
                'method' => 'get',
                'permission' => 'page_edit',
            ], [
                'ability' => 'pages.read',
                'permission' => 'page_edit',
                'permission_mode' => 'static',
                'security_risk' => 'normal',
                'accepted_credential_types' => ['interactive_access', 'service_token'],
                'requires_step_up' => false,
                'step_up_purposes' => [],
                'action_plan_eligible' => false,
                'domain_model_keys' => [],
                'transactional_outbox_model_keys' => [],
                'side_effect_kind' => 'read',
                'canonicalizer' => 'bridge',
                'target_resolver' => 'route_id',
                'idempotent' => false,
                'middleware' => [
                    'api_v2_ability:pages.read',
                    'api_v2_permission:page_edit',
                    'api_v2_website_scope',
                    'api_v2_risk_context',
                    'api_v2_risk',
                ],
            ]],
            'static write' => [[
                'name' => 'pages.builder.save',
                'method' => 'post',
                'permission' => 'page_edit',
            ], [
                'ability' => 'pages.write',
                'permission' => 'page_edit',
                'permission_mode' => 'static',
                'security_risk' => 'high',
                'accepted_credential_types' => ['interactive_access'],
                'requires_step_up' => false,
                'step_up_purposes' => [],
                'action_plan_eligible' => false,
                'domain_model_keys' => [],
                'transactional_outbox_model_keys' => [],
                'side_effect_kind' => 'external',
                'canonicalizer' => 'bridge',
                'target_resolver' => 'route_id',
                'idempotent' => false,
                'middleware' => [
                    'api_v2_ability:pages.write',
                    'api_v2_permission:page_edit',
                    'api_v2_website_scope',
                    'api_v2_risk_context',
                    'api_v2_risk',
                ],
            ]],
            'model-target write' => [[
                'name' => 'models.update',
                'method' => 'post',
                'permission_template' => '{model}_edit',
            ], [
                'ability' => 'models.write',
                'permission' => '{model}_edit',
                'permission_mode' => 'model_template',
                'security_risk' => 'high',
                'accepted_credential_types' => ['interactive_access'],
                'requires_step_up' => false,
                'step_up_purposes' => [],
                'action_plan_eligible' => false,
                'domain_model_keys' => [],
                'transactional_outbox_model_keys' => [],
                'side_effect_kind' => 'external',
                'canonicalizer' => 'dynamic_model',
                'target_resolver' => 'dynamic_model_ids',
                'idempotent' => false,
                'middleware' => [
                    'api_v2_ability:models.write',
                    'api_v2_model_permission:edit',
                    'api_v2_website_scope',
                    'api_v2_risk_context',
                    'api_v2_risk',
                ],
            ]],
        ];
    }

    /**
     * Verify unknown model permission templates fail closed.
     *
     * @return void
     */
    public function test_unknown_model_permission_template_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LegacyOperationSecurity::actionRequirements([
            'name' => 'models.unsafe',
            'method' => 'post',
            'permission_template' => '{model}_own_everything',
        ]);
    }

    /**
     * Verify a supported template cannot be attached to an unapproved bridge operation.
     *
     * @return void
     */
    public function test_model_permission_template_is_rejected_for_an_unknown_operation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LegacyOperationSecurity::actionRequirements([
            'name' => 'unsafe.update',
            'method' => 'post',
            'permission_template' => '{model}_edit',
        ]);
    }

    /**
     * Verify template syntax cannot be smuggled through a static resource permission.
     *
     * @return void
     */
    public function test_resource_static_permission_rejects_model_template_syntax(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LegacyOperationSecurity::resourceRequirements('links', 'update', [
            'permissions' => ['update' => '{model}_edit'],
        ]);
    }

    /**
     * Verify template syntax cannot be smuggled through a static bridge permission.
     *
     * @return void
     */
    public function test_bridge_static_permission_rejects_model_template_syntax(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LegacyOperationSecurity::actionRequirements([
            'name' => 'models.update',
            'method' => 'post',
            'permission' => '{model}_edit',
        ]);
    }
}

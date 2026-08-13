<?php

namespace Wncms\Tests\Unit\Api\V2;

use PHPUnit\Framework\Attributes\DataProvider;
use Wncms\Api\V2\ModelPermissionResolver;
use Wncms\Tests\TestCase;
use Wncms\Tests\Fixtures\Api\V2\MismatchedModelKey;
use Wncms\Tests\Fixtures\Api\V2\NonStaticModelKey;
use Wncms\Tests\Fixtures\Api\V2\NotAnEloquentModel;
use Wncms\Tests\Fixtures\Api\V2\PrivateModelKey;
use Wncms\Tests\Fixtures\Api\V2\Overrides\TrustedWidget;

class ModelPermissionResolverTest extends TestCase
{
    /**
     * Verify supported selectors normalize only through the explicit model allowlist.
     *
     * @param  string  $selector
     * @param  string  $suffix
     * @param  array{model_key: string, model_class: class-string, permission: string}  $expected
     * @return void
     */
    #[DataProvider('supportedSelectorProvider')]
    public function test_it_resolves_supported_model_permissions(
        string $selector,
        string $suffix,
        array $expected,
    ): void {
        $this->assertSame($expected, app(ModelPermissionResolver::class)->resolve($selector, $suffix));
    }

    /**
     * Provide supported selector mappings independently from production code.
     *
     * @return array<string, array{string, string, array{model_key: string, model_class: class-string, permission: string}}>
     */
    public static function supportedSelectorProvider(): array
    {
        return [
            'singular canonical' => ['user', 'edit', ['model_key' => 'user', 'model_class' => \Wncms\Models\User::class, 'permission' => 'user_edit']],
            'plural canonical' => ['users', 'bulk_delete', ['model_key' => 'user', 'model_class' => \Wncms\Models\User::class, 'permission' => 'user_bulk_delete']],
            'legacy studly selector' => ['User', 'edit', ['model_key' => 'user', 'model_class' => \Wncms\Models\User::class, 'permission' => 'user_edit']],
        ];
    }

    /**
     * Verify configured overrides return the exact validated class selected by WNCMS.
     *
     * @return void
     */
    public function test_it_returns_the_exact_configured_model_override_class(): void
    {
        $this->configureCatalogModel('trusted_widget', TrustedWidget::class);

        $this->assertSame([
            'model_key' => 'trusted_widget',
            'model_class' => TrustedWidget::class,
            'permission' => 'trusted_widget_edit',
        ], app(ModelPermissionResolver::class)->resolve('trusted_widgets', 'edit'));
    }

    /**
     * Verify invalid model classes and modelKey declarations fail closed without escaping throwables.
     *
     * @param  string  $modelKey
     * @param  string  $modelClass
     * @return void
     */
    #[DataProvider('invalidCatalogModelProvider')]
    public function test_it_rejects_invalid_catalog_model_metadata(string $modelKey, string $modelClass): void
    {
        $this->configureCatalogModel($modelKey, $modelClass);

        $this->assertNull(app(ModelPermissionResolver::class)->resolve($modelKey, 'edit'));
    }

    /**
     * Provide invalid catalog model declarations.
     *
     * @return array<string, array{string, string}>
     */
    public static function invalidCatalogModelProvider(): array
    {
        return [
            'private model key' => ['private_model_key', PrivateModelKey::class],
            'non-static model key' => ['non_static_model_key', NonStaticModelKey::class],
            'mismatched model key' => ['mismatched_model_key', MismatchedModelKey::class],
            'not eloquent' => ['not_eloquent', NotAnEloquentModel::class],
        ];
    }

    /**
     * Verify arbitrary classes, unknown models, and unsupported suffixes fail closed.
     *
     * @param  mixed  $selector
     * @param  string  $suffix
     * @return void
     */
    #[DataProvider('rejectedSelectorProvider')]
    public function test_it_rejects_untrusted_or_unsupported_model_permissions(mixed $selector, string $suffix): void
    {
        $this->assertNull(app(ModelPermissionResolver::class)->resolve($selector, $suffix));
    }

    /**
     * Provide rejected selector mappings.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function rejectedSelectorProvider(): array
    {
        return [
            'missing selector' => [null, 'edit'],
            'arbitrary class' => ['App\\Models\\User', 'edit'],
            'unknown model' => ['unknown_model', 'edit'],
            'unsupported suffix' => ['user', 'force_delete'],
        ];
    }

    /**
     * Add one model to the backend allowlist and WNCMS override map.
     *
     * @param  string  $modelKey
     * @param  string  $modelClass
     * @return void
     */
    private function configureCatalogModel(string $modelKey, string $modelClass): void
    {
        config([
            "wncms-backend-api-v2.resources.{$modelKey}" => [
                'model_key' => $modelKey,
                'enabled_actions' => [],
            ],
            "wncms.models.{$modelKey}" => ['class' => $modelClass],
        ]);
    }
}

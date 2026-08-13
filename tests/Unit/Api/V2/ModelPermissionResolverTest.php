<?php

namespace Wncms\Tests\Unit\Api\V2;

use PHPUnit\Framework\Attributes\DataProvider;
use Wncms\Api\V2\ModelPermissionResolver;
use Wncms\Tests\TestCase;

class ModelPermissionResolverTest extends TestCase
{
    /**
     * Verify supported selectors normalize only through the explicit model allowlist.
     *
     * @param  string  $selector
     * @param  string  $suffix
     * @param  array{model_key: string, permission: string}  $expected
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
     * @return array<string, array{string, string, array{model_key: string, permission: string}}>
     */
    public static function supportedSelectorProvider(): array
    {
        return [
            'singular canonical' => ['user', 'edit', ['model_key' => 'user', 'permission' => 'user_edit']],
            'plural canonical' => ['users', 'bulk_delete', ['model_key' => 'user', 'permission' => 'user_bulk_delete']],
            'legacy studly selector' => ['User', 'edit', ['model_key' => 'user', 'permission' => 'user_edit']],
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
}
